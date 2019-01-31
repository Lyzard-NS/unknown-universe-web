<?php
include_once( './core/core.php' );

header('Content-Type: application/json');

$PARAMS = [
    "username" => "Username",
    "password" => "Password",
    "email" => "Email",
    "g-recaptcha-response" => "ReCaptcha",
];

if (NEED_INVITATION) {
    $PARAMS["invitationCode"] = "Invitation Code";
}

$MISSING = [];
foreach ($PARAMS as $FIELD_NAME => $PARAM) {
    if ( !isset($_POST[$FIELD_NAME]) || empty($_POST[$FIELD_NAME])) {
        $MISSING[] = $PARAM;
    }
}


//MISSING PARAMETERS
if ( !empty($MISSING)) {
    http_response_code(400);
    die(json_encode(["message" => "Fill out " . implode(', ', $MISSING) . "!"]));
}

//VALIDATE GOOGLE_RECAPTCHA
$RECAPTCHA_RESPONSE = json_decode(
    file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify?secret=' .
        GOOGLE_CAPTCHA_KEY .
        '&response=' .
        $_POST['g-recaptcha-response']
    )
);
if ( !$RECAPTCHA_RESPONSE->success) {
    http_response_code(400);
    die(json_encode(["message" => "Check your ReCaptcha!"]));
}

//VALIDATE USERNAME
if (strlen($_POST['username']) < USERNAME_MIN_LENGTH) {
    http_response_code(400);
    die(json_encode(["message" => "Your chosen username is to short! Min length: " . USERNAME_MIN_LENGTH]));
}

if (strlen($_POST['username']) > USERNAME_MAX_LENGTH) {
    http_response_code(400);
    die(json_encode(["message" => "Your chosen username is to long! Max length: " . USERNAME_MAX_LENGTH]));
}

if ( !$System->validate->isValidString($_POST['username'], ALLOWED_CHARS)) {
    http_response_code(400);
    die(json_encode(["message" => "Your username can only contain [a-Z][0-9][" . implode(',', ALLOWED_CHARS) . "]!"]));
}

if ($System->validate->isUserByUsername($_POST['username'])) {
    http_response_code(400);
    die(json_encode(["message" => "Your chosen username is already taken!"]));
}

//VALIDATE PASSWORD
if (strlen($_POST['password']) < PASSWORD_MIN_LENGTH) {
    http_response_code(400);
    die(json_encode(["message" => "Your chosen password is to short! Min length: " . PASSWORD_MIN_LENGTH]));
}

if ($_POST['password'] == $_POST['username']) {
    http_response_code(400);
    die(json_encode(["message" => "Your username shouldn't be your password..."]));
}

//VALIDATE EMAIL
if ( !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die(json_encode(["message" => "Your E-Mail is invalid!"]));
}

if (
    strpos($_POST['email'], 'hotmail.com') !== false ||
    strpos(
        $_POST['email'],
        'hotmail.de'
    ) !== false ||
    strpos(
        $_POST['email'],
        'freemail.hu'
    ) !== false ||
    strpos(
        $_POST['email'],
        'outlook.de'
    ) !== false ||
    strpos($_POST['email'], 'outlook.com') !== false
) {
    http_response_code(400);
    die(
    json_encode(
        ["message" => "We can't support hotmail.com , hotmail.de,  freemail.hu, outlook.de, outlook.com e-mails!"]
    )
    );
}

if ($System->validate->isUserByEmail($_POST['email'])) {
    http_response_code(400);
    die(json_encode(["message" => "Your E-Mail is already taken! May try forgot password!"]));
}

//CHECK INVATION CODE
if (NEED_INVITATION) {
    if ( !$System->validate->isValidInvitation($_POST['invitationCode'])) {
        http_response_code(400);
        die(json_encode(["message" => "Invalid invitation code!"]));
    }
}

if ($System->registerUser($_POST['username'], $_POST['password'], $_POST['email'])) {
    $System->useInvitationCode($_POST['invitationCode']);
    http_response_code(201);
    die(json_encode(["message" => "You sucessfully registered!"]));
} else {
    http_response_code(500);
    die(json_encode(["message" => "Registration failed, try again later!"]));
}
