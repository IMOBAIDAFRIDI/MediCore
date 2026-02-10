<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

// 1. Create a dummy pending hospital
$test_email = 'imobaidafridi@gmail.com';
$conn->query("DELETE FROM hospitals WHERE email='$test_email'"); // Cleanup first

$sql = "INSERT INTO hospitals (name, email, password, address, location, status) 
        VALUES ('Test Hospital', '$test_email', 'password123', '123 Test St', 'Test City', 'pending')";

if ($conn->query($sql)) {
    $id = $conn->insert_id;
    echo "Created dummy hospital (ID: $id)...\n";
    
    // 2. Simulate Approval Logic (Copied from admin_requests.php for testing)
    echo "Simulating approval...\n";
    
    // Fetch hospital details for email
    $h_query = $conn->query("SELECT name, email FROM hospitals WHERE id=$id");
    if ($h_query && $h_query->num_rows > 0) {
        $h_data = $h_query->fetch_assoc();
        include_once 'mail_helper.php';
        
        $subject = "Registration Approved - MediCore TEST";
        $body = "Dear <strong>{$h_data['name']}</strong>,<br><br>
                 This is a TEST email to confirm that the Admin Approval logic works.<br>
                 Real Status: APPROVED.";
                 
        if(sendMail($h_data['email'], $subject, $body)) {
            echo "Email SENT successfully.\n";
        } else {
            echo "Email FAILED.\n";
        }
    } else {
        echo "Could not fetch hospital details.\n";
    }

    // 3. Cleanup
    $conn->query("DELETE FROM hospitals WHERE id=$id");
    echo "Test hospital deleted.\n";

} else {
    echo "Failed to create dummy hospital: " . $conn->error;
}
?>
