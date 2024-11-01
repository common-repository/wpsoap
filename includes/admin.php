<?php
/*
 * Admin Settings page view
 */
?>

<div class = "wrap" style="overflow: auto;">
    <h1>WP SOAP</h1>
    <div class="alert alert-info" role="alert"><div class="dashicons-before dashicons-editor-ul pull-left"></div> Available SOAP Methods</div>
    <?php
    ini_set("soap.wsdl_cache_enabled", 0);
    $client = new SoapClient(plugins_url() . "/wpsoap/wsdl/wpsoap.wsdl", array(' cache_wsdl ' => WSDL_CACHE_NONE));
    $count = 1;
    ?>
    <ul class="list-group">
        <?php foreach ($client->__getFunctions() as $method) { ?>
            <li class="list-group-item"><?php echo $method; ?><span class="badge pull-left p-a-3"><?php echo $count; ?></span> </li>
            <?php
            $count++;
        }
        ?>
    </ul>
    <div class="alert alert-info" role="alert"><div class="dashicons-before dashicons-menu pull-left"></div>Settings</div>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">WSDL PATH</th>
            <td><input type="text" name="wpsoap_wsdl_url" value="<?php echo esc_attr(plugins_url() . "/wpsoap/wsdl/wpsoap.wsdl"); ?>" readonly /></td>
        </tr>
        <tr valign="top">
            <th scope="row">SOAP SERVER URL</th>
            <td><input type="text" name="wpsoap_soapserver_url" value="<?php echo esc_attr(plugins_url() . "/wpsoap/soap-server.php"); ?>" readonly /></td>
        </tr>
    </table>
</div>



