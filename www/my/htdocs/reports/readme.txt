Варианты отображения уведомлений
<div class="message_info"><img src=/images/messages_info.png>Инфо</div>
<div class="message_success"><img src=/images/messages_success.png>Успех</div>
<div class="message_warning"><img src=/images/messages_warning.png>Внимание</div>
<div class="message_error"><img src=/images/messages_error.png>Ошибка</div>
<div class="message_validation"><img src=/images/messages_validation.png>Проверка</div>


Входящие данные для отчётов, все данные уже проверенны на ошибки, дополнительных проверок делать ненадо
 cars[] - массив машин по которым делать отчёт
 datefrom,dateto - период за который надо сформировать отчёт (переменные в формате dd.mm.yyyy)

именуется файл в таком порядке <корень>_<формат>.php
где "корень" - название отчёта, "формат" - формат отчёта, например html,xls. например сводный отчёт в excell будет именоваться так: svodniy_XLS.php

типы и форматы отчётов находятся в таблице settings записи typereport и formatreport
