<?php
/**
 * Quick and Dirty script to run ACH rescheduling against
 * what's currently in the standby table.
 */
function Main()
{
        global $server;
        $ach = ACH::Get_ACH_Handler($server, 'return');
                $ach->Reschedule_Apps(100);
}
?>

