<!-- resources/views/emails/confirmation_code.blade.php -->

<!DOCTYPE html>
<html>

<head>
    <title>Email Confirmation Code</title>
</head>

<body>
    <h1>Your Confirmation Code</h1>
    <p>Your confirmation code is: {{ $confirmationCode }}</p>
    <p>Please use this code to confirm your email address.</p>
</body>

</html>