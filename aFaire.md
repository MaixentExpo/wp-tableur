## À Faire

- edit si form_edit
- delete si delete=true
- ctrl attributs manquants rubrique
- column personnalisable sprintf
- action export csv
- ajout onglet aide.md

## Documentation

Add attachment and From header:

    <?php
    $attachments = array( WP_CONTENT_DIR . '/uploads/file_to_attach.zip' );
    $headers = 'From: My Name <myname@example.com>' . "\r\n";
    wp_mail( 'test@example.org', 'subject', 'message', $headers, $attachments );
    ?>

    add_action( 'phpmailer_init', 'mailer_config', 10, 1);
    function mailer_config(PHPMailer $mailer){
        $mailer->IsSMTP();
        $mailer->Host = "mail.telemar.it"; // your SMTP server
        $mailer->Port = 25;
        $mailer->SMTPDebug = 2; // write 0 if you don't want to see client/server communication in page
        $mailer->CharSet  = "utf-8";
    }
