<?php
session_start();
require_once '../config/dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];

    // Get car_id from booking and make sure this booking belongs to the logged-in user
    $stmt = $conn->prepare("SELECT car_id, status FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $booking = $result->fetch_assoc();

        if (strtolower($booking['status']) !== 'cancelled') {
            $car_id = $booking['car_id'];

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update booking status to cancelled
                $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $stmt->close();

                // Make the car available again
                $stmt = $conn->prepare("UPDATE cars SET type='available' WHERE id=?");
                $stmt->bind_param("i", $car_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $_SESSION['message'] = "Booking cancelled successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = "Error cancelling booking. Please try again.";
            }
        } else {
            $_SESSION['message'] = "Booking is already cancelled.";
        }
    } else {
        $_SESSION['message'] = "Booking not found.";
    }
} else {
    $_SESSION['message'] = "Invalid request.";
}

// Redirect back to bookings page
header("Location: bookings.php");
exit;
