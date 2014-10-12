<?php
header("Content-type: application/vnd.ms-excel");
header('Content-disposition: attachment; filename="report_' . date("Y-m-d") . '.xls"');
?>

<table>
<tr>
<td style="background-color: #FFFF00; text-align: center;  font-weight: bold; border-right: .5pt solid black; border-bottom: 1pt  solid black;">Column1</td>
<td style="background-color: #FFFF00; text-align: left; font-weight:  bold; border-right: .5pt solid black; border-bottom: 1pt solid  black;">Column2</td>
<td style="background-color: #FFFF00; text-align: left; font-weight:  bold; border-right: .5pt solid black; border-bottom: 1pt solid  black;">Column3</td>
.......
</table>
