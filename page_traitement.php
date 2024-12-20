<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Récupérer les données POST
$n = $_POST['name'];
$t = $_POST['phone'];
$m = $_POST['email'];
$s = $_POST['subject'];
$message = $_POST['message'];

// Nettoyer et valider l'email
$m = filter_var($m, FILTER_SANITIZE_EMAIL);
if (!filter_var($m, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $m)) {
    echo "<script>
            alert('Votre adresse email est invalide ou ce n\'est pas une adresse Gmail.');
            document.location.href = 'contact.html';
          </script>";
    exit; // Arrête l'exécution du script si l'email est invalide
}

// Préparer le message
$Message = "Nom : " . htmlspecialchars($n) . "\n<br>"
         . "Téléphone : " . htmlspecialchars($t) . "\n<br>"
         . "Email : " . htmlspecialchars($m) . "\n<br>"
         . "Sujet : " . htmlspecialchars($s) . "\n<br>"
         . "Message : " . nl2br(htmlspecialchars($message));

// Créer une instance de PHPMailer
$mail = new PHPMailer(true);

try {
    // Configurer le serveur SMTP
    $mail->isSMTP();                                            // Envoyer avec SMTP
    $mail->Host       = 'smtp.gmail.com';                       // Serveur SMTP
    $mail->SMTPAuth   = true;                                   // Activer l'authentification SMTP
    $mail->Username   = 'aboubakeribrahim0640@gmail.com';        // Nom d'utilisateur SMTP
    $mail->Password   = 'arzl akcc jwsy eico';                  // Mot de passe SMTP
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Activer le chiffrement TLS implicite
    $mail->Port       = 465;                                    // Port SMTP (TLS sécurisé)

    // Configurer les destinataires
    $mail->setFrom('from@example.com', 'AirDjibouti');
    $mail->addAddress('reservationairdjibouti@gmail.com');       // Ajouter un destinataire

    // Configurer le contenu
    $mail->isHTML(true);                                        // Format HTML
    $mail->Subject = 'Réservation de vol par Client';
    $mail->Body    = $Message;
    $mail->AltBody = strip_tags($Message);                      // Texte brut pour les clients email sans HTML

    // Envoyer l'email
    $mail->send();
    echo 'Votre message a été envoyé avec succès.';
} catch (Exception $e) {
    echo "Votre message n'a pas pu être envoyé. Erreur : {$mail->ErrorInfo}";
}
