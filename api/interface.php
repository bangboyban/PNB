<?php
$radius = ($Menu['data'] ? "and id in('$Menu[data]')" : "and users = '$Menu[id]'");
function Replace($data){
    $resul = str_replace(array('<','>'),'',$data);
    return $resul;
}
if(isset($_GET['data'])){
    $online = array();
    $setData = (empty($_GET['data']) ? "" : " and id = '".Rahmad($_GET['data'])."'");
    $routes = $Bsk->View(
        "nas", "id, identity, nasname, username, password, port, description", 
        "identity = '$Menu[identity]' and status = 'true' $radius ".$setData, "id asc"
    );
    foreach ($routes as $trafic) {
        $ports = ($trafic['port'] ? ":".$trafic['port'] : "");
        if ($Router->connect($trafic['nasname'].$ports, $trafic['username'], $Auth->decrypt($trafic['password'], 'BSK-RAHMAD'))) {
            $IPAddr = $Router->comm("/interface/print");
            foreach ($IPAddr as $IPList) {
                if(!$IPList['dynamic']){
                    $online[] = array(
                        "identity"  => $trafic['id'],
                        "router"    => $trafic['description'],
                        "name"      => Replace($IPList['name']),
                        "type"      => $IPList['type'],
                        "mac"       => $IPList['mac-address'],
                        "status"    => $IPList['disabled'],
                        "id"        => $IPList['.id']
                    );
                }
            }
        }
    }
    $Router->disconnect();
    $json_data = array(
		"draw"            => 1,
		"recordsTotal"    => count($online),
		"recordsFiltered" => count($online),
        "data"            => $online
	);
    echo json_encode($json_data, true);
}
if(isset($_GET['server'])){
    $server = array();
    $querys = $Bsk->View("nas", "id, description as name", "identity = '$Menu[identity]' ".$radius, "id asc");
    foreach ($querys as $hspLists) {
        $server[] = $hspLists;
    }
    echo json_encode($server ? 
        array("status" => true, "message" => "success", "color" => "green", "data" => $server) : 
        array("status" => false, "message" => "error", "color" => "red", "data" => false), true
    );
}
if(isset($_GET['detail'])){
    $id_detail = explode('*', $_GET['detail']);
    $query_detail = $Bsk->Tampil("nas", "id, nasname, username, password, port", "id = '$id_detail[0]' and identity = '$Menu[identity]' ".$radius);
    $showPort = ($query_detail['port'] ? ":".$query_detail['port'] : "");
    if ($Router->connect($query_detail['nasname'].$showPort, $query_detail['username'], $Auth->decrypt($query_detail['password'], 'BSK-RAHMAD'))) {
        $RoutShow = $Router->comm('/interface/print', array("?.id"=> '*'.$id_detail[1]));
    }
    $detail = array(
        "id"        => $query_detail['id'].$RoutShow[0]['.id'],
        "name"      => Replace($RoutShow[0]['name']),
        "type"      => $RoutShow[0]['type'],
        "mac"       => $RoutShow[0]['mac-address'],
        "comment"   => $RoutShow[0]['comment'],
        "status"    => ($RoutShow[0]['disabled'] == 'true' ? false: true)
    );
    echo json_encode($detail ? 
		array("status" => true, "message" => "success", "data" => $detail) : 
		array("status" => false, "message" => "error", "data" => false), true
	);
}
if(isset($_POST['name'])){
    $id_route = explode('*', $_POST['id']);
    $ps_unset = array('id', 'status');
    $disabled = (isset($_POST['status']) ? 'no' : 'yes'); 
    foreach ($ps_unset as $key) {
        unset($_POST[$key]);
    }
    $ip_route = $Bsk->Tampil("nas", "id, nasname, username, password, port", "id = '$id_route[0]' and identity = '$Menu[identity]' ".$radius);
    $ip_ports = ($ip_route['port'] ? ":".$ip_route['port'] : "");
    if ($Router->connect($ip_route['nasname'].$ip_ports, $ip_route['username'], $Auth->decrypt($ip_route['password'], 'BSK-RAHMAD'))) {
        $ip_query = array_merge($_POST, array('disabled' => $disabled));
        $post = $Router->comm('/interface/set', array_merge($ip_query, array(".id" => "*".$id_route[1])));
    }
    $Router->disconnect();
    echo json_encode($ip_query ? 
        array("status" => true, "message" => "success", "color" => "green", "data" => "Proccess data success.") : 
        array("status" => false, "message" => "error", "color" => "red", "data" => "Proccess data failed!"), true
    );
}
if(isset($_POST['active'])){
    $id_active = explode('*', $_POST['active']);
    $check_active = $Bsk->Tampil("nas", "id, nasname, username, password, port", "id = '$id_active[0]' and identity = '$Menu[identity]' ".$radius);
    $stausPort = ($check_active['port'] ? ":".$check_active['port'] : "");
    if ($Router->connect($check_active['nasname'].$stausPort, $check_active['username'], $Auth->decrypt($check_active['password'], 'BSK-RAHMAD'))) {
        $prints = $Router->comm('/interface/print', array("?.id"=> '*'.$id_active[1]));
        $status = ($prints[0]['disabled'] == 'true' ? 'enable' : 'disable');
        $Router->write('/interface/'.$status, false);
        $query_active = $Router->write('=.id=*'.$id_active[1], true);
        $Router->read();
    }
    $Router->disconnect();
    echo json_encode($query_active ? 
		array("status" => true, "message" => "success", "color" => "green", "data" => "Active data success") : 
		array("status" => false, "message" => "error", "color" => "red", "data" => "Active data failed!"), true
	);
}