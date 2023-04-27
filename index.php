<?php
session_start();

// Kullanıcı adı ve şifreyi kontrol et
if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if ($username === 'test' && $password === '123123qq') {
        // Kullanıcı adı ve şifre doğru, oturumu başlat
        $_SESSION['loggedin'] = true;
    } else {
        // Kullanıcı adı veya şifre yanlış, hata mesajı göster
        $error = 'Kullanıcı adı veya şifre yanlış';
    }
}

// Oturum doğrulaması kontrol et
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Oturum doğrulaması başarısız, login formunu göster
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1>Login</h1>
    <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php } ?>
    <form method="POST">
        <div class="mb-3">
            <label for="username" class="form-label">Kullanıcı adı</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Şifre</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Giriş yap</button>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
<?php
    exit;
}

// Oturum doğrulaması başarılı, e-postaları al
$hostname  = '{mail.site.com:993/imap/ssl}INBOX';
$username = 'info@site.com';
$password = 'sifre';

// gelen mailleri al
$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to mail server: ' . imap_last_error());

$emails = imap_search($inbox, 'ALL');

$messages = [];

if ($emails) {
    rsort($emails);

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $message = imap_fetchbody($inbox, $email_number, 1.1);
        if (empty($message)) {
            $message = imap_fetchbody($inbox, $email_number, 1);
        }
        $message = quoted_printable_decode($message);

        // Decode 'from' field
        $from = $overview[0]->from;
        $decodedFrom = imap_mime_header_decode($from);
        $from = '';
        $subject = $overview[0]->subject;
        $decodedSubject = imap_mime_header_decode($subject);
        $subject = '';

        foreach ($decodedFrom as $part) {
            $from .= $part->text;
        }

        foreach ($decodedSubject as $part) {
            $subject .= $part->text;
        }

        $subject = imap_utf8($subject);

    // Replace &nbsp; in the message content
    $message = str_replace('&nbsp;', ' ', $message);

        $from = imap_utf8($from);

        $attachments = [];

        $structure = imap_fetchstructure($inbox, $email_number);

        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                if ($structure->parts[$i]->ifdparameters) {
                    foreach ($structure->parts[$i]->dparameters as $object) {
                        if (strtolower($object->attribute) == 'filename') {
                            $filename = $object->value;
                            $attachments[$i] = ['filename' => $filename, 'index' => ($i + 1)];
                        }
                    }
                }
            }
        }

        $messages[] = [
            'overview' => $overview[0],
            'from' => $from,
            'subject' => $subject, // Add the 'subject' key to the array
            'message' => $message,
            'attachments' => $attachments,
        ];
    }
}

imap_close($inbox);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emails</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <table class="table">
        <thead>
        <tr>
            <th scope="col">From</th>
            <th scope="col">Subject</th>
            <th scope="col">Attachment</th>
            <th scope="col">Date</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($messages as $message): ?>
            <tr>
            <td><?= htmlspecialchars($message['from']) ?></td>
            <td><?= htmlspecialchars($message['subject']) ?></td>
                <td>
                    <?php foreach ($message['attachments'] as $attachment): ?>
                        <a href="download.php?email=<?= $message['overview']->msgno ?>&part=<?= $attachment['index'] ?>">
                            <?= htmlspecialchars($attachment['filename']) ?>
                        </a><br>
                        <?php endforeach; ?>
                </td>
                <td><?= htmlspecialchars(date("d-m-Y H:i:s", strtotime($message['overview']->date))) ?></td>
            </tr>
            <tr>
                <!-- <td colspan="4" style="display:none;" class="email-content">
                    <div><?= nl2br(htmlspecialchars($message['message'])) ?></div>
                </td> -->
                <td colspan="4" style="display:none;" class="email-content-cell">
    <div class="email-content"><?= nl2br(htmlspecialchars(strip_tags($message['message']))) ?></div>
</td>


            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.min.js"></script>
<script>
$(document).ready(function () {
    $('table').on('click', 'tr', function () {
        var emailContentCell = $(this).next('tr').find('.email-content-cell');
        emailContentCell.toggle();
    });
});

</script>
</body>
</html>

