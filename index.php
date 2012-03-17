<?php 
mysql_connect('localhost', 'root', '');
mysql_select_db('oaitest');
$q = mysql_query('select * from test');
while ($row=mysql_fetch_assoc($q))
{
print_r($row);
}

 ?>