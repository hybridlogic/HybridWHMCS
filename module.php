<?php
require("WHMCSAPI.class.php");
require("AdminAPI.class.php");

function hybridcluster_ConfigOptions() {

    # Should return an array of the module options for each product - maximum of 24

    $configarray = array(
        "Units" => array( "Type" => "text", "Size" => "5", "Description" => "HCU" ),
        "Monthly Transfer" => array( "Type" => "text", "Size" => "6", "Description" => "GB" ),
        "FTP accounts" => array( "Type" => "text", "Size" => "3", "Description" => "accounts"),
        "Email Accounts" => array( "Type" => "text", "Size" => "5", "Description" => "accounts"),
        "Databases" => array( "Type" => "text", "Size" => "5", "Description" => "databases"),
        "Disk Quota" => array( "Type" => "text", "Size" => "5", "Description" => "GB"),
    );

    return $configarray;
}

function hybridcluster_CreateAccount($params) {
    //mail("rob@hybrid-logic.com", "hybridcluster_CreateAccount", var_export($params, true));

    // Set the username to the domain name

    localAPI("UpdateClientProduct", Array(
        "serviceid" => $params['serviceid'],
        "serviceusername" => $params['domain'],
    ));
    $params['serviceusername'] = $params['domain'];

    $currencies = localAPI("GetCurrencies", Array(), HYBRIDCLUSTER_WHMCS_USER);
    $code = false;
    foreach ($currencies['currencies']['currency'] as $info)
        if ($info['id'] == $params['clientsdetails']['currency'])
            $code = $info['code'];

    if ($code === false)
        throw new Exception("Unknown currency ID #{$params['clientsdetails']['currency']}");

    $params['clientsdetails']['currencycode'] = $code;
    return hybridcluster_getWHMCSAPI()->create_account($params);
}

function hybridcluster_TerminateAccount($params) {
    return hybridcluster_getWHMCSAPI()->terminate_account($params);
}

function hybridcluster_setSuspended($params, $suspended=1) {
    return hybridcluster_getWHMCSAPI()->set_suspended(Array(
        "serviceid" => $params['serviceid'],
        "suspended" => $suspended,
    ));
}
function hybridcluster_SuspendAccount($params) {
    return hybridcluster_setSuspended($params, 1);
}
function hybridcluster_UnsuspendAccount($params) {
    return hybridcluster_setSuspended($params, 0);
}

function hybridcluster_ChangePassword($params) {
    try {
        return hybridcluster_getWHMCSAPI()->change_website_password($params);
    } catch (HybridClusterAPIException $e) {
        return $e->getMessage();
    }
}

function hybridcluster_ChangePackage($params) {

    # Code to perform action goes here...

    if ($successful) {
        $result = "success";
    } else {
        $result = "Error Message Goes Here...";
    }
    return $result;

}

function hybridcluster_ClientArea($params) {
    $token = hybridcluster_getAdminAPI()->get_login_token(Array("username" => $params['clientsdetails']['email']));

    # Output can be returned like this, or defined via a clientarea.tpl template file (see docs for more info)

    $code = '<form action="http://'.HYBRIDCLUSTER_CONTROL_PANEL_DOMAIN.'/website/domains#d:'.urlencode($params['domain']).'" method="post" target="_blank">
        <input type="hidden" name="_login_action" value="force_login" />
        <input type="hidden" name="username" value="'.$params['clientsdetails']['email'].'" />
        <input type="hidden" name="password" value="'.$token.'" />
        <input type="submit" value="Login to Control Panel" />
        <input type="button" value="Login to Webmail" onClick="window.open(\'http://'.HYBRIDCLUSTER_CONTROL_PANEL_DOMAIN.'/webmail/roundcube\')" />
        </form>';
    return $code;
}

function hybridcluster_LoginLink($params) {
    echo '<a href="http://'.HYBRIDCLUSTER_CONTROL_PANEL_DOMAIN.'/admin/edit-users?action=masq-user-website&amp;username='.urlencode($params['clientsdetails']['email']).'&amp;domain='.urlencode($params['domain']).'"  target="_blank" style="color:#cc0000">login to control panel</a>';
}

// Hooks start here

add_hook("ClientChangePassword", 1, "hybridcluster_hook_ClientChangePassword");
add_hook("ClientDetailsValidation", 1, "hybridcluster_hook_ClientDetailsValidation");

function hybridcluster_hook_ClientChangePassword($params) {
    return hybridcluster_getWHMCSAPI()->change_user_password($params);
}

/*
function hybridcluster_hook_ClientDetailsValidation() {
    var_dumP($_POST); die;
    return hybridcluster_getWHMCSAPI()->validate_user_details($_POST);
}
*/

function hybridcluster_getWHMCSAPI() {
    return new WHMCSAPI("https://" . HYBRIDCLUSTER_CONTROL_PANEL_DOMAIN,
        HYBRIDCLUSTER_CONTROL_PANEL_USERNAME,
        HYBRIDCLUSTER_CONTROL_PANEL_APIKEY, true);
}

function hybridcluster_getAdminAPI() {
    return new AdminAPI("https://" . HYBRIDCLUSTER_CONTROL_PANEL_DOMAIN,
        HYBRIDCLUSTER_CONTROL_PANEL_USERNAME,
        HYBRIDCLUSTER_CONTROL_PANEL_APIKEY, true);
}

function htmlescape($v) {
    return htmlentities($v, ENT_COMPAT, "UTF-8");
}

/*
function hybridcluster_AdminArea($params) {
}

function hybridcluster_reboot($params) {

    # Code to perform reboot action goes here...

    if ($successful) {
        $result = "success";
    } else {
        $result = "Error Message Goes Here...";
    }
    return $result;

}

function hybridcluster_shutdown($params) {

    # Code to perform shutdown action goes here...

    if ($successful) {
        $result = "success";
    } else {
        $result = "Error Message Goes Here...";
    }
    return $result;

}

function hybridcluster_ClientAreaCustomButtonArray() {
    $buttonarray = array(
        "Reboot Server" => "reboot",
    );
    return $buttonarray;
}

function hybridcluster_AdminCustomButtonArray() {
    $buttonarray = array(
        "Reboot Server" => "reboot",
        "Shutdown Server" => "shutdown",
    );
    return $buttonarray;
}

function hybridcluster_extrapage($params) {
    $pagearray = array(
        'templatefile' => 'example',
        'breadcrumb' => ' > <a href="#">Example Page</a>',
        'vars' => array(
            'var1' => 'demo1',
            'var2' => 'demo2',
        ),
    );
    return $pagearray;
}

function hybridcluster_UsageUpdate($params) {

    $serverid = $params['serverid'];
    $serverhostname = $params['serverhostname'];
    $serverip = $params['serverip'];
    $serverusername = $params['serverusername'];
    $serverpassword = $params['serverpassword'];
    $serveraccesshash = $params['serveraccesshash'];
    $serversecure = $params['serversecure'];

    # Run connection to retrieve usage for all domains/accounts on $serverid

    # Now loop through results and update DB

    foreach ($results AS $domain=>$values) {
        update_query("tblhosting",array(
            "diskused"=>$values['diskusage'],
            "dislimit"=>$values['disklimit'],
            "bwused"=>$values['bwusage'],
            "bwlimit"=>$values['bwlimit'],
            "lastupdate"=>"now()",
        ),array("server"=>$serverid,"domain"=>$values['domain']));
    }

}

function hybridcluster_AdminServicesTabFields($params) {

    $result = select_query("mod_customtable","",array("serviceid"=>$params['serviceid']));
    $data = mysql_fetch_array($result);
    $var1 = $data['var1'];
    $var2 = $data['var2'];
    $var3 = $data['var3'];
    $var4 = $data['var4'];

    $fieldsarray = array(
        'Field 1' => '<input type="text" name="modulefields[0]" size="30" value="'.$var1.'" />',
        'Field 2' => '<select name="modulefields[1]"><option>Val1</option</select>',
        'Field 3' => '<textarea name="modulefields[2]" rows="2" cols="80">'.$var3.'</textarea>',
        'Field 4' => $var4, # Info Output Only
    );
    return $fieldsarray;

}

function hybridcluster_AdminServicesTabFieldsSave($params) {
    update_query("mod_customtable",array(
        "var1"=>$_POST['modulefields'][0],
        "var2"=>$_POST['modulefields'][1],
        "var3"=>$_POST['modulefields'][2],
    ),array("serviceid"=>$params['serviceid']));
}
 */
