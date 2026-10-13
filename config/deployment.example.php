<?php
/**
 * Copy this file to config/deployment.php and fill in the values from
 * the InfinityFree Client Area. Do not commit or publicly share deployment.php.
 *
 * InfinityFree notes:
 * - Outbound SMTP (ports 587/465) is blocked, so mail_transport must be 'brevo'
 *   (the Brevo HTTP API on port 443).
 * - The sending address must be verified in Brevo (Transactional > Senders),
 *   otherwise Gmail/Outlook may route the email to spam or reject it.
 * - Diagnostics are written to assets/cache/mail.log and shown on
 *   admin/mail-test.php.
 */
return [
    'app_url' => 'https://your-domain.example',

    'db_host' => 'sqlXXX.infinityfree.com',
    'db_name' => 'if0_XXXXXXXX_database',
    'db_user' => 'if0_XXXXXXXX',
    'db_pass' => 'replace-with-database-password',

    'mail_transport' => 'brevo',
    'brevo_api_key' => 'replace-with-brevo-api-key',

    // Use a sender address that has been verified in your Brevo account.
    'smtp_port' => '587',
    'smtp_encryption' => 'tls',
    'smtp_from_email' => 'verified-sender@example.com',
    'smtp_from_name' => 'DONMA Attendance Tracking System',

    // Set to true while debugging delivery problems. The reset page then
    // shows the real mail error instead of the generic notice. Set to false
    // before normal use.
    'mail_debug' => false,

    // Used only when mail_transport is set to smtp (not available on InfinityFree).
    'smtp_host' => '',
    'smtp_username' => '',
    'smtp_password' => '',
];
