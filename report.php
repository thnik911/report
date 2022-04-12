<?php
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");
ini_set('error_reporting', E_ALL);

//AUTH
require_once 'auth.php';

$date1 = $_REQUEST['date1'];
$date2 = $_REQUEST['date2'];

$startdate = date_format(date_create($date1), 'c');
$enddate = date_format(date_create($date2), 'c');

$warning = 0;
$start = 0;
$iNumPage = 1;
$count = 0;
$finish = 1;
$startdeal = 0;
$countdeal = 0;
$pagenum2foruser = 1;
$iNumPageUser = 1;
$countforact = 0;
$prosr = 0;
$startallact = 0;
$pagenum2foract = 1;
$iNumPageAct = 1;
$globalcount = 0;
$alldeals = 0;
$alldealswihoutact = 0;
$allclosecat = 0;
$allprostact = 0;
$summUSD = 0;
$globalcountfordeals = 0;

$datenow = date(DATE_ATOM);

echo "<table border='1'>
<caption>Бонус Экспертов</caption>
<tr>
<th>Сотрудник</th>
<th>Всего сделок в работе</th>
<th>Всего сделок без дел</th>
<th>Всего завершенных дел</th>
<th>Всего просроченных дел</th>
<th>Сумма $</th>
</tr>";

while ($iNumPageUser == $pagenum2foruser) {

    $userlist = executeREST(
        'user.get',
        array(
            'order' => array(
                'ID' => 'ASC',
            ),
            'filter' => array(
                'UF_DEPARTMENT' => array(474, 475, 486),
                'ACTIVE' => true,
                'USER_TYPE' => 'employee',

            ),
            'select' => array(
                "ID",
            ),
            'start' => $start,
        ),
        $domain, $auth, $user);

    $totalusers = $userlist['total'];

    $pagenum1foruser = $totalusers / 50;
    $pagenum2foruser = ceil($pagenum1foruser);

    while ($count != 50 and $globalcount <= $totalusers) {

        if ($globalcount == $totalusers) {

            break;
        }
        $emploeeID = $userlist['result'][$count]['ID'];
        if (empty($emploeeID)) {

            break;
        } elseif (empty($userlist) and (empty($emploeeID))) {
            break 2;
        }
        $emploeeName = $userlist['result'][$count]['NAME'];
        $emploeeLastName = $userlist['result'][$count]['LAST_NAME'];
        $cryt = 'Y';

        while ($cryt == 'Y') {

            $deallist = executeREST(
                'crm.deal.list',
                array(
                    'order' => array(
                        'ID' => 'ASC',
                    ),
                    'filter' => array(
                        'CLOSED' => 'N',
                        'CATEGORY_ID' => 27,
                        'ASSIGNED_BY_ID' => $emploeeID,

                    ),
                    'select' => array(
                        "ID",
                    ),
                    'start' => $startdeal,
                ),
                $domain, $auth, $user);
            $totaldeals = $deallist['total'];

            $pagenum1 = $totaldeals / 50;
            $pagenum2 = ceil($pagenum1);

            if ($totaldeals == 0) {
                $warning = 0;
                break;
            }

            while ($countdeal != $totaldeals and $totaldeals > 1 and $countdeal <= 49) {
                $deal = $deallist['result'][$countdeal]['ID'];
                $activityinfo = executeREST(
                    'crm.activity.list',
                    array(
                        'filter' => array(
                            'OWNER_ID' => $deal,
                            'COMPLETED' => 'N',
                            // '>CREATED' => $startdate,
                            // '<CREATED' => $enddate,
                            '<TYPE_ID' => 3,
                        ),
                        'select' => array(
                            "ID", "LAST_UPDATED", "DEADLINE",
                        ),
                    ),
                    $domain, $auth, $user);

                $countact = $activityinfo['total'];

                if ($countact == 0) {
                    $warning = $warning + 1;
                }
                $countdeal = $countdeal + 1;
                $globalcountfordeals = $globalcountfordeals + 1;

                if ($globalcountfordeals == $totaldeals) {
                    break;
                }
            }

            $countdeal = 0;

            if ($pagenum2 == $iNumPage) {
                $cryt = 'N';
            } else {
                $iNumPage = $iNumPage + 1;
            }

            $startdeal = $startdeal + 50;
        }
        $startdeal = 0;
        $globalcountfordeals = 0;

//закрытые дела

        while ($pagenum2foract == $iNumPageAct) {

            $activityinfoall = executeREST(
                'crm.activity.list',
                array(
                    'filter' => array(
                        'RESPONSIBLE_ID' => $emploeeID,
                        'COMPLETED' => 'Y',
                        '>CREATED' => $startdate,
                        '<CREATED' => $enddate,
                        '<TYPE_ID' => 3,
                    ),
                    'select' => array(
                        "ID", "LAST_UPDATED", "DEADLINE",
                    ),
                    'start' => $startallact,
                ),
                $domain, $auth, $user);
            $allactivities = $activityinfoall['total'];
            $pagenum1foract = $allactivities / 50;
            $pagenum2foract = ceil($pagenum1foract);

            $countforactY = 0;
            if ($allactivities != 0) {
                while ($allactivities != $countforactY and $countforactY != 49) {

                    $allactivitieslU = $activityinfoall['result'][$countforactY]['LAST_UPDATED'];
                    $allactivitiesDE = $activityinfoall['result'][$countforactY]['DEADLINE'];
                    if ($allactivitieslU > $allactivitiesDE) {
                        $prosr = $prosr + 1;
                    }
                    $countforactY = $countforactY + 1;

                }
            }
            $iNumPageAct = $iNumPageAct + 1;
            $startallact = $startallact + 50;
        }

//октрытые дела

        $pagenum2foract = 1;
        $iNumPageAct = 1;
        $countforactN = 0;

        while ($pagenum2foract == $iNumPageAct) {

            $activityinfoall = executeREST(
                'crm.activity.list',
                array(
                    'filter' => array(
                        'RESPONSIBLE_ID' => $emploeeID,
                        'COMPLETED' => 'N',
                        '>CREATED' => $startdate,
                        '<CREATED' => $enddate,
                        '<TYPE_ID' => 3,
                    ),
                    'select' => array(
                        "ID", "LAST_UPDATED", "DEADLINE",
                    ),
                    'start' => $startallact,
                ),
                $domain, $auth, $user);
            $allactivitiesN = $activityinfoall['total'];
            $pagenum1foract = $allactivitiesN / 50;
            $pagenum2foract = ceil($pagenum1foract);
            if ($allactivitiesN != 0) {
                $countforactN = 0;
                while ($allactivitiesN != $countforactN and $countforactN != 49) {

                    $allactivitiesDE = $activityinfoall['result'][$countforactN]['DEADLINE'];
                    if ($allactivitiesDE < $datenow) {
                        $prosr = $prosr + 1;
                    }
                    $countforactN = $countforactN + 1;

                }
            }
            $iNumPageAct = $iNumPageAct + 1;
            $startallact = $startallact + 50;
        }

        $count = $count + 1;
        $globalcount = $globalcount + 1;

        $summ = ($allactivities - $prosr - $warning) * 1;

        echo "<tr><td>$emploeeName</td><td>$totaldeals</td><td>$warning</td><td>$allactivities</td><td>$prosr</td><td>$summ</td></tr>";

        $alldeals = $alldeals + $totaldeals;
        $alldealswihoutact = $alldealswihoutact + $warning;
        $allclosecat = $allclosecat + $allactivities;
        $allprostact = $allprostact + $prosr;
        $summUSD = $summUSD + $summ;

        $warning = 0;
        $totaldeals = 0;
        $prosr = 0;
        $countforact = 0;
        $iNumPageAct = 1;
        $pagenum2foract = 1;
        $iNumPage = 1;

    }

    //tyt

    $iNumPageUser = $iNumPageUser + 1;
    $start = $start + 50;
    $count = 0;

}

echo "<tr><td><strong>Сумма</strong></td><td><strong>$alldeals</strong></td><td><strong>$alldealswihoutact</strong></td><td><strong>$allclosecat</strong></td><td><strong>$allprostact</strong></td><td><strong>$summUSD</strong></td></tr>";
echo "</table>";
function executeREST($method, array $params, $domain, $auth, $user)
{
    $queryUrl = 'https://' . $domain . '/rest/' . $user . '/' . $auth . '/' . $method . '.json';
    $queryData = http_build_query($params);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));
    return json_decode(curl_exec($curl), true);
    curl_close($curl);
}

function writeToLog($data, $title = '')
{
    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s") . "\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";
    file_put_contents(getcwd() . '/report.log', $log, FILE_APPEND);
    return true;
}
