<?php
// Connexion à la base de données
$host = '127.0.0.1';
$dbname = 'djibouti';
$username = 'root'; // Changez selon vos paramètres
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Démarrer la session
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['passenger_id'])) {
    header('Location: connecter.php');
    exit;
}

// Récupérer l'ID du passager
$passenger_id = $_SESSION['passenger_id'];

// Traitement de l'annulation d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $booking_id = $_POST['cancel_booking_id'];

    // Vérification des conditions d'annulation
    $query = $pdo->prepare("
        SELECT b.*, f.scheduled_departure 
        FROM bookings b 
        JOIN flights f ON b.flight_id = f.flight_id 
        WHERE b.booking_id = :booking_id AND b.passenger_id = :passenger_id
    ");
    $query->execute(['booking_id' => $booking_id, 'passenger_id' => $passenger_id]);
    $booking = $query->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $scheduled_departure = new DateTime($booking['scheduled_departure']);
        $current_time = new DateTime();

        // Vérifiez si l'annulation est possible (par exemple, au moins 24 heures avant le départ)
        if ($scheduled_departure > $current_time->add(new DateInterval('P1D'))) {
            // Mise à jour du statut de la réservation
            $updateQuery = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = :booking_id");
            $updateQuery->execute(['booking_id' => $booking_id]);
            $message = "Votre réservation a été annulée avec succès.";
        } else {
            $error = "L'annulation est uniquement possible 24 heures avant le départ.";
        }
    } else {
        $error = "Réservation introuvable ou non autorisée.";
    }
}

// Récupérer les vols disponibles
$flightsQuery = $pdo->query("
    SELECT f.flight_id, f.flight_number, a1.name AS departure_airport, a2.name AS arrival_airport, 
           f.scheduled_departure, f.scheduled_arrival, f.status, f.base_price 
    FROM flights f
    JOIN airports a1 ON f.departure_airport_id = a1.airport_id
    JOIN airports a2 ON f.arrival_airport_id = a2.airport_id
    WHERE f.status = 'scheduled'
    ORDER BY f.scheduled_departure ASC
");

// Récupérer les réservations du passager
$bookingsQuery = $pdo->prepare("
    SELECT b.booking_id, f.flight_number, a1.name AS departure_airport, a2.name AS arrival_airport, 
           f.scheduled_departure, b.status 
    FROM bookings b
    JOIN flights f ON b.flight_id = f.flight_id
    JOIN airports a1 ON f.departure_airport_id = a1.airport_id
    JOIN airports a2 ON f.arrival_airport_id = a2.airport_id
    WHERE b.passenger_id = :passenger_id
    ORDER BY f.scheduled_departure ASC
");
$bookingsQuery->execute(['passenger_id' => $passenger_id]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vols Disponibles & Annulations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .table-container {
            margin-top: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .btn-custom {
            background-color: #003366;
            color: white;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background-color: #00509e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4">Vols Disponibles</h1>
        <div class="table-container">
            <h3>Vols Disponibles</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Numéro de Vol</th>
                        <th>Départ</th>
                        <th>Arrivée</th>
                        <th>Départ Programmé</th>
                        <th>Prix</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($flight = $flightsQuery->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($flight['flight_id']) ?></td>
                            <td><?= htmlspecialchars($flight['flight_number']) ?></td>
                            <td><?= htmlspecialchars($flight['departure_airport']) ?></td>
                            <td><?= htmlspecialchars($flight['arrival_airport']) ?></td>
                            <td><?= htmlspecialchars($flight['scheduled_departure']) ?></td>
                            <td><?= htmlspecialchars($flight['base_price']) ?> $</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container mt-5">
            <h3>Vos Réservations</h3>
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Numéro de Vol</th>
                        <th>Départ</th>
                        <th>Arrivée</th>
                        <th>Départ Programmé</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookingsQuery->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['booking_id']) ?></td>
                            <td><?= htmlspecialchars($booking['flight_number']) ?></td>
                            <td><?= htmlspecialchars($booking['departure_airport']) ?></td>
                            <td><?= htmlspecialchars($booking['arrival_airport']) ?></td>
                            <td><?= htmlspecialchars($booking['scheduled_departure']) ?></td>
                            <td><?= htmlspecialchars($booking['status']) ?></td>
                            <td>
                                <?php if ($booking['status'] === 'confirmed'): ?>
                                    <form method="POST" onsubmit="return confirmCancellation(event)">
                                        <input type="hidden" name="cancel_booking_id" value="<?= $booking['booking_id'] ?>">
                                        <button type="submit" class="btn btn-custom">Annuler</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Non modifiable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function confirmCancellation(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action ne peut pas être annulée.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#003366',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        }
    </script>
</body>
</html>
