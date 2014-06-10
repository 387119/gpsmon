<?
    require_once ("../config.php");

    function pg_array_parse($text, &$output, $limit = false, $offset = 1) // преобразование массива psql -> php
    {
        if (false === $limit) {
            $limit = strlen($text) - 1;
            $output = array();
        }
        if ('{}' != $text)
            do {
                if ('{' != $text{$offset}) {
                    preg_match("/(\\{?\"([^\"\\\\]|\\\\.)*\"|[^,{}]+)+([,}]+)/", $text, $match, 0, $offset);
                    $offset += strlen($match[0]);
                    $output[] = ('"' != $match[1]{0} ? $match[1] : stripcslashes(substr($match[1], 1, -1 )));
                    if('},' == $match[3]) return $offset;
                } else$offset = pg_array_parse($text, $output[], $limit, $offset+1);
            } while($limit > $offset);
        return $output;
    }

 
    switch ($_REQUEST['action']) {
        case 'getGeozones':
            $query = "SELECT * FROM geozones WHERE carid=$_REQUEST[carid]";
            $result = pg_query($connection, $query);
            if (pg_num_rows($result) > 0) {
                $result_array = pg_fetch_all($result);
            }

            if (!empty($result_array)) {
                foreach ($result_array as &$arr) { // преобразование массива postgresql в массив php
                    $arr['lat'] = pg_array_parse($arr['lat'], $arr['lat']);
                    $arr['lon'] = pg_array_parse($arr['lon'], $arr['lon']);
                }
            }
            echo json_encode($result_array);
            break;

        case 'getCarInfo':
            $query = "SELECT name,gosnum FROM cars WHERE carid=$_REQUEST[carid]";
            $result = pg_query($connection, $query);
            if ($row = pg_fetch_row($result)) {
                echo json_encode($row);
            }
            break;

        case 'changeAlertEmail':
            $query = "UPDATE settings SET value='$_REQUEST[email]' WHERE name='geozone_alert_email'";
            $result = pg_query($connection, $query);
            break;

        case 'saveGeozone':
            $geozone = json_decode($_REQUEST['data']);
            $glat = implode(',', $geozone->latitude);
            $glon = implode(',', $geozone->longitude);
            $id = $geozone->id;
            $local_id = $geozone->local_id;
            $carid = $geozone->carid;
            $name = $geozone->name;
            $type = $geozone->overlay_type;
            $radius = $geozone->radius > 0 ? $geozone->radius : 0;
            if ($id) {
                $query = "UPDATE geozones SET carid=$carid,name='$name',type='$type',lon=ARRAY[$glon],lat=ARRAY[$glat],radius=$radius WHERE id=$id";
            } else {
                $query = "INSERT INTO geozones (carid,name,type,lat,lon,radius) VALUES ($carid,'$name','$type',ARRAY[$glat],ARRAY[$glon],$radius)";
            }
            $result = pg_query($connection, $query);
            
            if ($result && is_null($id)) { // INSERT
                $query = "SELECT id FROM geozones ORDER BY id DESC";
                $result = pg_query($connection, $query);
                if ($row = pg_fetch_row($result)) {
                    echo json_encode(array('id' => $row[0]));
                }
            }
            break;

        case 'deleteGeozone':
            $id = $_REQUEST['id'];
            if ($id) {
                $query = "DELETE FROM geozones WHERE id=$id";
                $result = pg_query($connection, $query);
            }
            break;
    }

?>

