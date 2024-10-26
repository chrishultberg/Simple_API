<?php

function sendEmailReport($pdo, $htmlReport, $absent) {

    // Ensure absent is a boolean; default to false if not explicitly true
    $absent = isset($absent) ? filter_var($absent, FILTER_VALIDATE_BOOLEAN) : false;

    // Fetch email addresses
    $emailQuery = "SELECT type, address FROM email_addresses";
    $emailStmt = $pdo->prepare($emailQuery);
    $emailStmt->execute();
    $emails = $emailStmt->fetchAll(PDO::FETCH_ASSOC);

    $to = $cc = $bcc = [];

    foreach ($emails as $email) {
        switch (strtolower($email['type'])) {
            case 'to':
                $to[] = $email['address'];
                break;
            case 'cc':
                $cc[] = $email['address'];
                break;
            case 'bcc':
                $bcc[] = $email['address'];
                break;
        }
    }

    if (empty($to)) {
        return ['status' => 'error', 'message' => 'No recipients found.'];
    }

    $subject = ($absent === true ? 'Absent' : 'Attendance') . ' Report';
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@yourdomain.com" . "\r\n";
    
    if (!empty($cc)) {
        $headers .= "Cc: " . implode(',', $cc) . "\r\n";
    }
    
    if (!empty($bcc)) {
        $headers .= "Bcc: " . implode(',', $bcc) . "\r\n";
    }

    $toList = implode(',', $to);

    // Send email
    $sent = mail($toList, $subject, $htmlReport, $headers);

    if ($sent) {
        return ['status' => 'success', 'message' => 'Email sent successfully.'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to send email.'];
    }
}
?>
