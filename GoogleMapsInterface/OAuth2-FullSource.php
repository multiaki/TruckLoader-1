<?
$OAuth = array(
    'oauth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'client_id' => '700318837628-29bc38gesa2qk5jacq0s8kh36dso2sqo.apps.googleusercontent.com',
    'client_secret' => 'ZcARk-P8g8nrztOBtwSXhybF',
    'redirect_uri' => 'http://example.com/OAuth2', // insert your redirect URI here
    'oauth_token_uri' => 'https://accounts.google.com/o/oauth2/token',
);
$token = array(
    'access_token' => '',
    'token_type' => '',
    'expires_in' => '',
    'refresh_token' => ''
);

$title = 'No Code';
$AuthCode = 'Null';

// see if error parameter exisits
$error = _get_url_param($_SERVER['REQUEST_URI'], 'error');
if ($error != NULL)
{   // this means the user denied api access to GWMTs
    $title = $error;
}
else
{   // does the code parameter exist?
    $AuthCode = _get_url_param($_SERVER['REQUEST_URI'], 'code');
    if ($AuthCode == NULL)
    {   // get authorization code
        $OAuth_request = _formatOAuthReq($OAuth,
                        "https://www.google.com/webmasters/tools/feeds/sites/");

        header('Location: ' . $OAuth_request);
        exit; // the redirect will come back to this page and $code will have a value
    }
    else
    {
        $title = 'Got Authorization Code';
        // now exchange Authorization code for access token and refresh token
        $token_response = _get_auth_token($OAuth, $AuthCode);
        $json_obj = json_decode($token_response);
        $token['access_token'] = $json_obj->access_token;
        $token['token_type'] = $json_obj->token_type;
        $token['expires_in'] = $json_obj->expires_in;
        $token['refresh_token'] = $json_obj->refresh_token;

        $sites = _get_wmt_sites_feed($token);
    }
}

// Return the list of sites registered in your Google Webmaster Tools
function _get_wmt_sites_feed($access_tokens)
{
    $post_string = "https://www.google.com/webmasters/tools/feeds/sites/";
    $post_string .= '?v=2';
    $post_string .= '&oauth_token=' . $access_tokens['access_token'];

    $response = file_get_contents($post_string);
    return _parse_wmt_sites_response($response);
}

function _parse_wmt_sites_response($response)
{
    $xml = simplexml_load_string($response);
    $response = '<br />';
    foreach ($xml->entry as $entry)
    {
        foreach ($entry->title as $title)
        {
            $response .= "<p><a href=\"$title\" target=\"_blank\">$title</a></p>";
        }
    }
    return $response;
}

function _get_auth_token($params, $code)
{
    $url = $params['oauth_token_uri'];

    $fields = array(
        'code' => $code,
        'client_id' => $params['client_id'],
        'client_secret' => $params['client_secret'],
        'redirect_uri' => $params['redirect_uri'],
        'grant_type' => 'authorization_code'
    );
    $response = _do_post($url, $fields);
    return $response;
}

function _do_post($url, $fields)
{
    $fields_string = '';

    foreach ($fields as $key => $value)
    {
        $fields_string .= $key . '=' . $value . '&';
    }
    $fields_string = rtrim($fields_string, '&');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

function _formatOAuthReq($OAuthParams, $scope)
{
    $uri = $OAuthParams['oauth_uri'];
    $uri .= "?client_id=" . $OAuthParams['client_id'];
    $uri .= "&redirect_uri=" . $OAuthParams['redirect_uri'];
    $uri .= "&scope=" . $scope;
    $uri .= "&response_type=code";

	//Following 2 lines added by Kevin 12-18-2011
    $uri .= "&access_type=offline";
    $uri .= "&approval_prompt=force";

    return $uri;
}

function _get_url_param($url, $name)
{
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    return isset($params[$name]) ? $params[$name] : null;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title><?= $title; ?></title>
    </head>
    <body>
        <h1>OAuth2 Authorization Code</h1>
        <p>Authorization Code: <?= $AuthCode; ?></p>
        <p>access token: <?= $token['access_token']; ?></p>
        <p>expires in: <?= $token['expires_in']; ?></p>
        <p>refresh token: <?= $token['refresh_token']; ?></p>
        <p><?=$sites;?></p>

    </body>
</html>