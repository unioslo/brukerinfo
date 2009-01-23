<?php
require_once '../init.php';
$Init = new Init();
$User = new User();

$View = View::create();
$View->addTitle('Email');
$View->addTitle('Delete');


$emails = array('joakimsh@student.matnat.uio.no', 'joakimsh@extern.matnat.uio.no', 'joakim.hovlandsvag@utenfor.student.matnat.uio.no');



if(isset($_POST['delete'])) {

    //take the first one (should only be one anyway)
    $toRemove = array_pop(array_keys($_POST['delete']));

    if(!isset($emails[$toRemove])) {
        View::addMessage('Unknown email address to delete', View::MSG_WARNING);
        View::forward('email/delete.php');
    }

    $rmAddress = $emails[$toRemove];

    //TODO: no checks here on valid emails...

    $View->addTitle('Confirm');
    $View->start();

    echo <<<CONFIRMATION
    <h1>Confirm deletion</h1>

    <form method="post" action="email/delete.php">

    <p>Do you really want to delete the email address <em>$rmAddress</em>?</p>

    <p>
        <input type="submit" class="submit_warn" name="confirmation" value="Yes, delete my addresses">
        <input type="submit" class="submit" value="No, cancel">
        <span class="explain">Remember that only a <a href="http://usit.uio.no/it/lita">LITA</a> or <a href="http://houston.uio.no">superuser</a> can create new addresses.</span>
    </p>

    </form>

CONFIRMATION;

    die;

}





$View = View::create();
$View->start();
echo "<h1>Delete email alias</h1>\n";

echo <<<CONTENT

<form method="post" action="email/delete.php">

<p>
    What email-address do you want to delete?
</p>

<table>
<tbody>
    <tr class="odd">
    <td>joakimsh@student.matnat.uio.no</td>
    <td><input type="submit" name="delete[0]" value="Delete"></td>
    </tr>

    <tr class="even">
    <td>joakimsh@extern.matnat.uio.no</td>
    <td><input type="submit" name="delete[1]" value="Delete"></td>
    </tr>

    <tr class="odd">
    <td>joakim.hovlandsvag@utenfor.student.matnat.uio.no</td>
    <td><input type="submit" name="delete[2]" value="Delete"></td>
    </tr>
</tbody>
</table>

</form>

CONTENT;


?>
