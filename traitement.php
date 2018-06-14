<?php

/**
 * @brief parse un lien de tradedoubler et retourne un tableaux de marchands avec uniquement les données nécessaires
 *
 * @param $xmlFile : URL à parser
 * @return array    : le tableau contenant les marchands
 */
function parseXMLTradedoubler($xmlFile)
{
    $voucherList = [];

    //Va chercher l'xml
    $xml = simplexml_load_file($xmlFile);

    //On parcourt tous les marchands de l'xml pour ne prendre que les arguments que nous désirons
    foreach ($xml->children() as $voucher) {

        $voucherJson = new Voucher();

        $voucherJson->title = $voucher->title;
        $voucherJson->voucher = $voucher->programName;
        $voucherJson->exclusive = $voucher->exclusive;
        $voucherJson->description = $voucher->description;
        $voucherJson->code = $voucher->code;

        //TODO : Les date-heure ne sont pas justes
        $voucherJson->startDate = date('d/m/Y H:i:s', floatval($voucher->startDate));
        $voucherJson->endDate = date('d/m/Y H:i:s', floatval($voucher->endDate));
        $voucherJson->landingUrl = $voucher->defaultTrackUri;

        $voucherList[] = $voucherJson;

    }

    return $voucherList;
}

/**
 * @brief parse un lien de affili et retourne un tableaux de marchands avec uniquement les données nécessaires
 *
 * @param $xmlFile : url où se trouve le xml
 * @return array   : le tableau contenant les marchands
 */
function parseXMLAffili($xmlFile)
{
    $voucherList = [];

    //Va chercher l'xml
    $xml = simplexml_load_file($xmlFile);

    //On parcourt tous les marchands de l'xml pour ne prendre que les arguments que nous désirons
    foreach ($xml->children()->children() as $item) {


        $voucherJson = new Voucher();

        $voucherJson->title = $item->title;
        $voucherJson->description = $item->description;

        $voucherList[] = $voucherJson;

    }

    unset($voucherList[0]);
    unset($voucherList[1]);
    unset($voucherList[2]);
    unset($voucherList[3]);

    return $voucherList;
}

/**
 * @brief parse un lien d'Awin et retourne un tableaux de marchands avec uniquement les données nécessaires
 *
 * @param $csvfile : url où se trouve le xml
 * @return array   : le tableau contenant les marchands
 */
function parseCSVAwin($csvfile)
{

    /**
     * 0 Promotion ID
     * 1 Advertiser
     * 2 Advertiser ID
     * 3 Type
     * 4 Code
     * 5 Description
     * 6 Starts
     * 7 Ends
     * 8 Categories
     * 9 Regions
     * 10 Terms
     * 11 Deeplink Tracking
     * 12 Deeplink
     * 13 Commission Groups
     * 14 Commission
     * 15 Exclusive
     * 16 Date Added
     * 17 Title
     */

    $voucherList = [];

    $data = file_get_contents($csvfile);
    $rows = explode("\n", $data);
    foreach ($rows as $rowRAW) {
        $row = str_getcsv($rowRAW);

        $voucherJson = new Voucher();

        $voucherJson->title = ifExist($row, 17); //title
        $voucherJson->voucher = ifExist($row, 1); //Advertiser
        $voucherJson->exclusive = ifExist($row, 15); //Exclusive
        $voucherJson->description = ifExist($row, 5);// Description
        $voucherJson->code = ifExist($row, 4); // Code
        $voucherJson->startDate = ifExist($row, 6); // Starts
        $voucherJson->endDate = ifExist($row, 7); // ends
        $voucherJson->landingUrl = ifExist($row, 11); // Deeplink Tracking

        $voucherList[] = $voucherJson;
    }

    //On supprime la première ligne du csv qui était la description des champs
    unset($voucherList[0]);

    return $voucherList;
}

/**
 * @brief retourne l'élément du tableau s'il existe à l'index donné
 *
 * @param $tab : tableau duquel prendre l'élément
 * @param $index : index de l'élément à prendre dans le tableau
 * @return string   : l'élément à l'index donné ou ERROR s'il n'y avait rien
 */
function ifExist($tab, $index)
{
    return count($tab) > $index ? $tab[$index] : "ERROR";
}


/**
 * @brief envoie un mail avec les informations suivantes sur un marchand :
 *
 * Marchand, Exclusif, Description, Code, Date début, Date fin, Lien
 *
 * @param $to : à qui envoie le mail
 * @param $voucher : Objet Voucher contenant toutes les informations d'un marchand
 */
function sendmail($to, $voucher)
{

    $subject = $voucher->title;

    $headers = "From: test.test@test.ch\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

    $message = '<html><body>';
    //$message .= '<img src="//css-tricks.com/examples/WebsiteChangeRequestForm/images/wcrf-header.png" alt="Website Change Request" />';
    $message .= '<table rules="all" style="border-color: #666;" cellpadding="10">';
    $message .= "<tr style='background: #eee;'><td><strong>Marchand</strong> </td><td>" . $voucher->voucher . "</td></tr>";
    $message .= "<tr><td><strong>Exclusif</strong> </td><td>" . $voucher->exclusive . "</td></tr>";
    $message .= "<tr><td><strong>Description</strong> </td><td>" . $voucher->description . "</td></tr>";
    $message .= "<tr><td><strong>Code</strong> </td><td>" . $voucher->code . "</td></tr>";
    $message .= "<tr><td><strong>Date début</strong> </td><td>" . $voucher->startDate . "</td></tr>";
    $message .= "<tr><td><strong>Date fin</strong> </td><td>" . $voucher->endDate . "</td></tr>";
    $message .= "<tr><td><strong>Lien</strong> </td><td>" . $voucher->landingUrl . "</td></tr>";
    $message .= "</table>";
    $message .= "</body></html>";

    return mail($to, $subject, $message, $headers);
}

class Voucher
{
    public $title = "";
    public $voucher = "";
    public $exclusive = "";
    public $description = "";
    public $code = "";
    public $startDate = "";
    public $endDate = "";
    public $landingUrl = "";
}

/**
 * ---------------------------------------------------------------------------------------------------------------------
 *                                                PROGRAMME PRINCIPAL
 * ---------------------------------------------------------------------------------------------------------------------
 */

$QUERY_AWI = "https://ui.awin.com";
$QUERY_TRADEDOUBLER = "http://api.tradedoubler.com";
$QUERY_AFFILI = "https://modules.affili.net";

$email = $_POST['email'];
$url = $_POST['url'];


/**
 * Suivant le site donné en paramètre, on effectue un parsage différent
 */
$voucherList = new ArrayObject();

if (substr($url, 0, strlen($QUERY_AWI)) === $QUERY_AWI) {
    $voucherList = parseCSVAwin($url);
} else if (substr($url, 0, strlen($QUERY_TRADEDOUBLER)) === $QUERY_TRADEDOUBLER) {
    $voucherList = parseXMLTradedoubler($url);
} else if (substr($url, 0, strlen($QUERY_AFFILI)) === $QUERY_AFFILI) {
    $voucherList = parseXMLAffili($url);
} else {
    echo "Vous n'avez pas entrez une url correct";
}

$i = 0;
$voucherTest = new Voucher();

?>
<html>
<body>
<?php
foreach ($voucherList as $voucher) {

    if ($i == 0) {
        $voucherTest = $voucher;
    }


    ?>
    <table rules="all" style="border-color: #666;" cellpadding="10">
        <tr style='background: #eef;'>
            <td><strong>Titre</strong></td>
            <td><?= $voucher->title ?> </td>
        </tr>
        <tr style='background: #eee;'>
            <td><strong>Marchand</strong></td>
            <td><?= $voucher->voucher ?> </td>
        </tr>
        <tr>
            <td><strong>Exclusif</strong></td>
            <td><?= $voucher->exclusive ?></td>
        </tr>
        <tr>
            <td><strong>Description</strong></td>
            <td><?= $voucher->description ?></td>
        </tr>
        <tr>
            <td><strong>Code</strong></td>
            <td><?= $voucher->code ?></td>
        </tr>
        <tr>
            <td><strong>Date début</strong></td>
            <td><?= $voucher->startDate ?></td>
        </tr>
        <tr>
            <td><strong>Date fin</strong></td>
            <td><?= $voucher->endDate ?></td>
        </tr>
        <tr>
            <td><strong>Lien</strong></td>
            <td><?= $voucher->landingUrl ?></td>
        </tr>
    </table>
    <?php
    /*
        echo $voucher->title . ", ";
        echo $voucher->voucher . ", ";
        echo $voucher->exclusive . ", ";
        echo $voucher->description . ", ";
        echo $voucher->code . ", ";
        echo $voucher->startDate . ", ";
        echo $voucher->endDate . ", ";
        echo $voucher->landingUrl . "</br>" . "</br>";
    */
    $i++;
}

?>
</body>
</html>
<?php

if (sendmail($email, $voucherTest)) {
    echo "Ouiiiiii :)";
} else {
    echo "Noooooon :(";
}
?>


