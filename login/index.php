<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main login page.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once('lib.php');

redirect_if_major_upgrade_required();

$testsession = optional_param('testsession', 0, PARAM_INT); // test session works properly
$anchor      = optional_param('anchor', '', PARAM_RAW);     // Used to restore hash anchor to wantsurl.
$loginredirect = optional_param('loginredirect', 1, PARAM_BOOL);   // Used to bypass alternateloginurl.

$resendconfirmemail = optional_param('resendconfirmemail', false, PARAM_BOOL);

// It might be safe to do this for non-Behat sites, or there might
// be a security risk. For now we only allow it on Behat sites.
// If you wants to do the analysis, you may be able to remove the
// if (BEHAT_SITE_RUNNING).
if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
    $wantsurl    = optional_param('wantsurl', '', PARAM_LOCALURL);   // Overrides $SESSION->wantsurl if given.
    if ($wantsurl !== '') {
        $SESSION->wantsurl = (new moodle_url($wantsurl))->out(false);
    }
}

$context = context_system::instance();
$PAGE->set_url("$CFG->wwwroot/login/index.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

/// Initialize variables
$errormsg = '';
$infomsg = '';
$errorcode = 0;

// login page requested session test
if ($testsession) {
    if ($testsession == $USER->id) {
        if (isset($SESSION->wantsurl)) {
            $urltogo = $SESSION->wantsurl;
        } else {
            $urltogo = $CFG->wwwroot.'/';
        }
        unset($SESSION->wantsurl);
        redirect($urltogo);
    } else {
        // TODO: try to find out what is the exact reason why sessions do not work
        $errormsg = get_string("cookiesnotenabled");
        $errorcode = 1;
    }
}

/// Check for timed out sessions
if (!empty($SESSION->has_timed_out)) {
    $session_has_timed_out = true;
    unset($SESSION->has_timed_out);
} else {
    $session_has_timed_out = false;
}

$frm  = false;
$user = false;

$authsequence = get_enabled_auth_plugins(); // Auths, in sequence.
foreach($authsequence as $authname) {
    $authplugin = get_auth_plugin($authname);
    // The auth plugin's loginpage_hook() can eventually set $frm and/or $user.
    $authplugin->loginpage_hook();
}


/// Define variables used in page
$site = get_site();

// Ignore any active pages in the navigation/settings.
// We do this because there won't be an active page there, and by ignoring the active pages the
// navigation and settings won't be initialised unless something else needs them.
$PAGE->navbar->ignore_active();
$loginsite = get_string("loginsite");
$PAGE->navbar->add($loginsite);

if ($user !== false or $frm !== false or $errormsg !== '') {
    // some auth plugin already supplied full user, fake form data or prevented user login with error message

} else if (!empty($SESSION->wantsurl) && file_exists($CFG->dirroot.'/login/weblinkauth.php')) {
    // Handles the case of another Moodle site linking into a page on this site
    //TODO: move weblink into own auth plugin
    include($CFG->dirroot.'/login/weblinkauth.php');
    if (function_exists('weblink_auth')) {
        $user = weblink_auth($SESSION->wantsurl);
    }
    if ($user) {
        $frm->username = $user->username;
    } else {
        $frm = data_submitted();
    }

} else {
    $frm = data_submitted();
}

// Restore the #anchor to the original wantsurl. Note that this
// will only work for internal auth plugins, SSO plugins such as
// SAML / CAS / OIDC will have to handle this correctly directly.
if ($anchor && isset($SESSION->wantsurl) && strpos($SESSION->wantsurl, '#') === false) {
    $wantsurl = new moodle_url($SESSION->wantsurl);
    $wantsurl->set_anchor(substr($anchor, 1));
    $SESSION->wantsurl = $wantsurl->out();
}

/// Check if the user has actually submitted login data to us

if ($frm and isset($frm->username)) {                             // Login WITH cookies

    $frm->username = trim(core_text::strtolower($frm->username));

    if (is_enabled_auth('none') ) {
        if ($frm->username !== core_user::clean_field($frm->username, 'username')) {
            $errormsg = get_string('username').': '.get_string("invalidusername");
            $errorcode = 2;
            $user = null;
        }
    }

    if ($user) {
        // The auth plugin has already provided the user via the loginpage_hook() called above.
    } else if (($frm->username == 'guest') and empty($CFG->guestloginbutton)) {
        $user = false;    /// Can't log in as guest if guest button is disabled
        $frm = false;
    } else {
        if (empty($errormsg)) {
            $logintoken = isset($frm->logintoken) ? $frm->logintoken : '';
            $loginrecaptcha = login_captcha_enabled() ? $frm->{'g-recaptcha-response'} ?? '' : false;
            $user = authenticate_user_login($frm->username, $frm->password, false, $errorcode, $logintoken, $loginrecaptcha);
        }
    }

    // Intercept 'restored' users to provide them with info & reset password
    if (!$user and $frm and is_restored_user($frm->username)) {
        $PAGE->set_title(get_string('restoredaccount'));
        $PAGE->set_heading($site->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('restoredaccount'));
        echo $OUTPUT->box(get_string('restoredaccountinfo'), 'generalbox boxaligncenter');
        require_once('restored_password_form.php'); // Use our "supplanter" login_forgot_password_form. MDL-20846
        $form = new login_forgot_password_form('forgot_password.php', array('username' => $frm->username));
        $form->display();
        echo $OUTPUT->footer();
        die;
    }

    if ($user) {

        // language setup
        if (isguestuser($user)) {
            // no predefined language for guests - use existing session or default site lang
            unset($user->lang);

        } else if (!empty($user->lang)) {
            // unset previous session language - use user preference instead
            unset($SESSION->lang);
        }

        if (empty($user->confirmed)) {       // This account was never confirmed
            $PAGE->set_title(get_string("mustconfirm"));
            $PAGE->set_heading($site->fullname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string("mustconfirm"));
            if ($resendconfirmemail) {
                if (!send_confirmation_email($user)) {
                    echo $OUTPUT->notification(get_string('emailconfirmsentfailure'), \core\output\notification::NOTIFY_ERROR);
                } else {
                    echo $OUTPUT->notification(get_string('emailconfirmsentsuccess'), \core\output\notification::NOTIFY_SUCCESS);
                }
            }
            echo $OUTPUT->box(get_string("emailconfirmsent", "", s($user->email)), "generalbox boxaligncenter");
            $resendconfirmurl = new moodle_url('/login/index.php',
                [
                    'username' => $frm->username,
                    'password' => $frm->password,
                    'resendconfirmemail' => true,
                    'logintoken' => \core\session\manager::get_login_token()
                ]
            );
            echo $OUTPUT->single_button($resendconfirmurl, get_string('emailconfirmationresend'));
            echo $OUTPUT->footer();
            die;
        }

    /// Let's get them all set up.
        complete_user_login($user);

        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

        // sets the username cookie
        if (!empty($CFG->nolastloggedin)) {
            // do not store last logged in user in cookie
            // auth plugins can temporarily override this from loginpage_hook()
            // do not save $CFG->nolastloggedin in database!

        } else if (empty($CFG->rememberusername)) {
            // no permanent cookies, delete old one if exists
            set_moodle_cookie('');

        } else {
            set_moodle_cookie($USER->username);
        }

        $urltogo = core_login_get_return_url();

    /// check if user password has expired
    /// Currently supported only for ldap-authentication module
        $userauth = get_auth_plugin($USER->auth);
        if (!isguestuser() and !empty($userauth->config->expiration) and $userauth->config->expiration == 1) {
            $externalchangepassword = false;
            if ($userauth->can_change_password()) {
                $passwordchangeurl = $userauth->change_password_url();
                if (!$passwordchangeurl) {
                    $passwordchangeurl = $CFG->wwwroot.'/login/change_password.php';
                } else {
                    $externalchangepassword = true;
                }
            } else {
                $passwordchangeurl = $CFG->wwwroot.'/login/change_password.php';
            }
            $days2expire = $userauth->password_expire($USER->username);
            $PAGE->set_title($loginsite);
            $PAGE->set_heading("$site->fullname");
            if (intval($days2expire) > 0 && intval($days2expire) < intval($userauth->config->expiration_warning)) {
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordwillexpire', 'auth', $days2expire), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            } elseif (intval($days2expire) < 0 ) {
                if ($externalchangepassword) {
                    // We end the session if the change password form is external. This prevents access to the site
                    // until the password is correctly changed.
                    require_logout();
                } else {
                    // If we use the standard change password form, this user preference will be reset when the password
                    // is changed. Until then it will prevent access to the site.
                    set_user_preference('auth_forcepasswordchange', 1, $USER);
                }
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordisexpired', 'auth'), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            }
        }

        // Discard any errors before the last redirect.
        unset($SESSION->loginerrormsg);
        unset($SESSION->logininfomsg);

        // Discard loginredirect if we are redirecting away.
        unset($SESSION->loginredirect);

        // test the session actually works by redirecting to self
        $SESSION->wantsurl = $urltogo;
        redirect(new moodle_url(get_login_url(), array('testsession'=>$USER->id)));

    } else {
        if (empty($errormsg)) {
            if ($errorcode == AUTH_LOGIN_UNAUTHORISED) {
                $errormsg = get_string("unauthorisedlogin", "", $frm->username);
            } else if ($errorcode == AUTH_LOGIN_FAILED_RECAPTCHA) {
                $errormsg = get_string('missingrecaptchachallengefield');
            } else {
                $errormsg = get_string("invalidlogin");
                $errorcode = 3;
            }
        }
    }
}

/// Detect problems with timedout sessions
if ($session_has_timed_out and !data_submitted()) {
    $errormsg = get_string('sessionerroruser', 'error');
    $errorcode = 4;
}

/// First, let's remember where the user was trying to get to before they got here

if (empty($SESSION->wantsurl)) {
    $SESSION->wantsurl = null;
    $referer = get_local_referer(false);
    if ($referer &&
            $referer != $CFG->wwwroot &&
            $referer != $CFG->wwwroot . '/' &&
            $referer != $CFG->wwwroot . '/login/' &&
            strpos($referer, $CFG->wwwroot . '/login/?') !== 0 &&
            strpos($referer, $CFG->wwwroot . '/login/index.php') !== 0) { // There might be some extra params such as ?lang=.
        $SESSION->wantsurl = $referer;
    }
}

// Check if loginredirect is set in the SESSION.
if ($errorcode && isset($SESSION->loginredirect)) {
    $loginredirect = $SESSION->loginredirect;
}
$SESSION->loginredirect = $loginredirect;

/// Redirect to alternative login URL if needed
if (!empty($CFG->alternateloginurl) && $loginredirect) {
    $loginurl = new moodle_url($CFG->alternateloginurl);

    $loginurlstr = $loginurl->out(false);

    if ($SESSION->wantsurl != '' && strpos($SESSION->wantsurl, $loginurlstr) === 0) {
        // We do not want to return to alternate url.
        $SESSION->wantsurl = null;
    }

    // If error code then add that to url.
    if ($errorcode) {
        $loginurl->param('errorcode', $errorcode);
    }

    redirect($loginurl->out(false));
}

/// Generate the login page with forms

if (!isset($frm) or !is_object($frm)) {
    $frm = new stdClass();
}

if (empty($frm->username) && $authsequence[0] != 'shibboleth') {  // See bug 5184
    if (!empty($_GET["username"])) {
        // we do not want data from _POST here
        $frm->username = clean_param($_GET["username"], PARAM_RAW); // we do not want data from _POST here
    } else {
        $frm->username = get_moodle_cookie();
    }

    $frm->password = "";
}

if (!empty($SESSION->loginerrormsg) || !empty($SESSION->logininfomsg)) {
    // We had some messages before redirect, show them now.
    $errormsg = $SESSION->loginerrormsg ?? '';
    $infomsg = $SESSION->logininfomsg ?? '';
    unset($SESSION->loginerrormsg);
    unset($SESSION->logininfomsg);

} else if ($testsession) {
    // No need to redirect here.
    unset($SESSION->loginerrormsg);
    unset($SESSION->logininfomsg);

} else if ($errormsg or !empty($frm->password)) {
    // We must redirect after every password submission.
    if ($errormsg) {
        $SESSION->loginerrormsg = $errormsg;
    }

    // Add redirect param to url.
    $loginurl = new moodle_url('/login/index.php');
    $loginurl->param('loginredirect', $SESSION->loginredirect);

    redirect($loginurl->out(false));
}

$PAGE->set_title($loginsite);
$PAGE->set_heading("$site->fullname");

echo $OUTPUT->header();


// Añade el botón de MetaMask y el script de integración aquí
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Web3 Login process with MetaMask</title>
        <script src="https://cdn.jsdelivr.net/npm/ethers@5.6.9/dist/ethers.umd.min.js" type="application/javascript"></script>
        <script>

        function web3_check_metamask() {
                if (!window.ethereum) {
                    console.error('It seems that the MetaMask extension is not detected. Please install MetaMask first.');
                    alert('It seems that the MetaMask extension is not detected. Please install MetaMask first.');
                    return false;
                }else{
                    console.log('MetaMask extension has been detected!!');
                    return true;
                }
        }

        async function web3_metamask_login() {
                if (web3_check_metamask()) {
                    console.log('Initate Login Process');

                    const provider = new ethers.providers.Web3Provider(window.ethereum);
                    await provider.send("eth_requestAccounts", []);
                    console.log("Connected!!");
                    const address = await provider.getSigner().getAddress();
                    console.log(address);
                    
                    const usernameInput = document.querySelector('#username');
                        usernameInput && (usernameInput.value = address); // Llenar el campo de entrada o no hacer nada si no existe


                    // Send the address to the server for creating a new user
                    try {
                        const response = await fetch('create_moodle_user.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ ethereumAddress: address })
                        });
                        const result = await response.json();
                        alert('User created successfully! Default password: ' + result.defaultPassword);
                    } catch (error) {
                        console.error('Error creating user:', error);
                        alert('Error creating user. Please try again later.');
                    }
                }
            } 


/*
console.log('Script cargado y ejecutándose');

document.addEventListener('DOMContentLoaded', () => {
    const connectButton = document.createElement('button');
    connectButton.textContent = 'Login with MetaMask';
    connectButton.id = 'connect-metamask';
    connectButton.className = 'btn btn-primary btn-block mt-3';
    
    const loginButton = document.querySelector('button[type="submit"]');
    if (loginButton) {
        loginButton.insertAdjacentElement('afterend', connectButton);
        console.log('Script cargado y ejecutándose 2');
    }

    console.log('Script cargado y ejecutándose 3');
    connectButton.addEventListener('click', async () => {
        console.log('Script cargado y ejecutándose 4');
        const accounts = await window.ethereum?.request({ method: 'eth_requestAccounts' });
        const provider = new ethers.providers.Web3Provider(window.ethereum);
        const signer = provider.getSigner();
        const userAddress = await signer.getAddress();

        console.log('Dirección de la wallet:', userAddress); // Agrega este log para verificar si se obtiene la dirección

        const usernameInput = document.querySelector('#username');
        usernameInput && (usernameInput.value = userAddress); // Llenar el campo de entrada o no hacer nada si no existe

        // Firmar un mensaje para autenticar
        const signature = await signer.signMessage("Autenticación con Moodle");
        console.log('Firma generada:', signature); // Agrega este log para verificar la firma

        fetch('verify_metamask.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ address: userAddress, signature: signature })
        }).then(response => response.json())
        .then(data => {
            data.success && (window.location.href = 'dashboard.php');
            alert(!data.success ? 'Error en la verificación: ' + data.message : '');
        });

        window.ethereum === undefined && alert('¡MetaMask no está instalado!');
    });

});
*/
</script>
</head>
    <body>
        <p>Check first if MetaMask is installed: <a href="#!" onclick="web3_check_metamask();">Detect MetaMask</a></p>
        <p>Initate the Login process: <a href="#!" onclick="web3_metamask_login();">Login with MetaMask</a></p>
    </body>
</html>
<?php

if (isloggedin() and !isguestuser()) {
    // Lógica existente
} else {
    $loginform = new \core_auth\output\login($authsequence, $frm->username);
    $loginform->set_error($errormsg);
    $loginform->set_info($infomsg);
    echo $OUTPUT->render($loginform);

}


/*if (isloggedin() and !isguestuser()) {
    // prevent logging when already logged in, we do not want them to relogin by accident because sesskey would be changed
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url('/login/logout.php', array('sesskey'=>sesskey(),'loginpage'=>1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url('/'), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('alreadyloggedin', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
} else {
    $loginform = new \core_auth\output\login($authsequence, $frm->username);
    $loginform->set_error($errormsg);
    $loginform->set_info($infomsg);
    echo $OUTPUT->render($loginform);
}*/

echo $OUTPUT->footer();


