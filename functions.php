<?
class DB{
    const HOST = 'localhost';
    const DBNAME = 'staff';
    const USER = 'root';
    const PASS = '';
    static $dbn = null;

    static function conn(){
        if(self::$dbn == null){
            self::$dbn = new PDO("mysql:host=".self::HOST.";dbname=".self::DBNAME, self::USER, self::PASS);
        }
        return self::$dbn;
    }
    static function close(){
        self::$dbn = null;
    }
}
function report_for_september(){
    $ar1 = [];
    $ar2 = [];
    $ar_new = [];

    $sql0 = <<<SQL
select start,end,date_format(start,'%d.%m.%Y') as dat from statistics
where work = 0
order by start
SQL;

    $sql2 = <<<SQL
select s.start,s.end,count(case when w.id=1 then 1 end) as w1,count(case when w.id=2 then 2 end) as w2,count(case when w.id=3 then 3 end) as w3,sum(bed*30+towels*10+price) as summ,date_format(s.start,'%d.%m.%Y') as dat from statistics as s

join works as w
on
s.work=w.id

join users as u
on
s.staff=u.id

join rooms as r
on
s.room=r.id

join builds as b
on
r.build=b.id

join prices as p
on
p.room_type=r.type
and
p.work=w.id
and
p.hotel=b.hotel

where s.start >= '2020-09-01 00:00:00' and s.end < '2020-10-01 00:00:00'

group by dat

order by start
SQL;

    $sql3 = <<<SQL
select sum(bed*30+towels*10+price) as summ from statistics as s

join works as w
on
s.work=w.id

join users as u
on
s.staff=u.id

join rooms as r
on
s.room=r.id

join builds as b
on
r.build=b.id

join prices as p
on
p.room_type=r.type
and
p.work=w.id
and
p.hotel=b.hotel

where s.start >= '2020-09-01 00:00:00' and s.end < '2020-10-01 00:00:00'

order by start
SQL;

    try {
        $dbh = DB::conn();
        foreach($dbh->query($sql0,PDO::FETCH_ASSOC) as $row) {
            array_push($ar1,array('start'=>$row['start'],'end'=>$row['end'],'dat'=>$row['dat']));
        }

        foreach($dbh->query($sql2,PDO::FETCH_ASSOC) as $row) {
            array_push($ar2,array('w1'=>$row['w1'],'w2'=>$row['w2'],'w3'=>$row['w3'],'summ'=>$row['summ']));
        }

        for($i=0;$i<count($ar1);$i++){
            array_push($ar_new,array_merge($ar1[$i],$ar2[$i]));
        }

        $res = $dbh->query($sql3,PDO::FETCH_ASSOC);
        $row_itog = $res->fetch();

        $str = "<h1>Отчет по работам Чистых Елена за сентябрь</h1>";
        $str .= "<table><tr><th>дата</th><th>начало р.дня</th><th>конец р.дня</th><th>кол-во ген.уборок</th>
            <th>кол-во тек.уборок</th><th>кол-во заездов</th><th>сумма оплаты за день</th>";
        foreach($ar_new as $v){
            $str .= "<tr>
                    <td><a href='#{$v['dat']}'>{$v['dat']}</a></td>
                    <td>{$v['start']}</td>
                    <td>{$v['end']}</td>
                    <td>{$v['w2']}</td>
                    <td>{$v['w3']}</td>
                    <td>{$v['w1']}</td>
                    <td>{$v['summ']}</td>
                </tr>";
        }
        $str .= "</table>";
        $str .= "<h2>сумма оплаты за сентябрь: {$row_itog['summ']} руб.</h2>";
        echo $str;

        DB::close();
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}

function report_for_day($day){
    $ar1 = [];
    $sql4 = <<<SQL
select s.start,s.end,s.room,b.name as build,r.type,r.floor,(bed*30+towels*10+price) as summ from statistics as s

join works as w
on
s.work=w.id

join users as u
on
s.staff=u.id

join rooms as r
on
s.room=r.id

join builds as b
on
r.build=b.id

join prices as p
on
p.room_type=r.type
and
p.work=w.id
and
p.hotel=b.hotel

where(date_format(s.start,'%d.%m.%Y') = :day)

order by start
SQL;

    $sql5 = <<<SQL
select sum(bed*30+towels*10+price) as summ from statistics as s

join works as w
on
s.work=w.id

join users as u
on
s.staff=u.id

join rooms as r
on
s.room=r.id

join builds as b
on
r.build=b.id

join prices as p
on
p.room_type=r.type
and
p.work=w.id
and
p.hotel=b.hotel

where(date_format(s.start,'%d.%m.%Y') = :day)

order by start
SQL;

    try {
        $dbh = DB::conn();
        $res = $dbh->prepare($sql4);
        $res->execute(array('day'=>$day));
        foreach($res->fetchAll(PDO::FETCH_ASSOC) as $row) {
            array_push($ar1,array('room'=>$row['room'],'build'=>$row['build'],'floor'=>$row['floor'],'type'=>$row['type'],'start'=>$row['start'],'end'=>$row['end'],'summ'=>$row['summ']));
        }

        $res = $dbh->prepare($sql5);
        $res->execute(array('day'=>$day));
        $row_itog = $res->fetch();

        $str = "<h1>Отчет по работам Чистых Елена за выбранный день ($day)</h1>";
        $str .= "<table><tr><th>номер</th><th>корпус</th><th>тип номера</th><th>тип уборки</th>
            <th>начало уборки</th><th>конец уборки</th><th>сумма за уборку</th>";
        foreach($ar1 as $v){
            $str .= "<tr>
                    <td>{$v['room']}</td>
                    <td>{$v['build']}</td>
                    <td>{$v['floor']}</td>
                    <td>{$v['type']}</td>
                    <td>{$v['start']}</td>
                    <td>{$v['end']}</td>
                    <td>{$v['summ']}</td>
                </tr>";
        }
        $str .= "</table>";
        $str .= "<h2>итоговая сумма за день: {$row_itog['summ']} руб.</h2>";
        $str .= "<a href='http://staff/'>на главную</a>";
        echo $str;

        DB::close();
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
}