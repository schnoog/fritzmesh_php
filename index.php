<?php


require_once( __DIR__  . "/vendor/autoload.php");
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/func.php");



use blacksenator\fritzsoap\hosts;

$makeclean = true;

$lifecall = false;
$tfile['mesh'] = __DIR__ . "/_mesh_cache";
$tfile['host'] = __DIR__ . "/_host_cache";

/**
 * Data input
 */
if($lifecall){
        $fritz_user = $CONFIG['fritz']['user']; 
        $fritz_url = $CONFIG['fritz']['host'];
        $fritz_pwd = $CONFIG['fritz']['pass'];    
        $fritzbox = new hosts($fritz_url, $fritz_user, $fritz_pwd);
        $fritzbox->getClient();
        $data =  $fritzbox->getHostList();
        $tmp =  $fritzbox->getMeshList(true);
        $meshdata = json_decode($tmp,true);
        $hostdata = json_decode(json_encode($data),true);
        file_put_contents($tfile['mesh'], json_encode($meshdata));
        file_put_contents($tfile['host'], json_encode($hostdata));  
}else{
        $meshdata = json_decode( file_get_contents($tfile['mesh']), true);
        $hostdata = json_decode( file_get_contents($tfile['host']), true);
}
/**
 * HostArray
 */
$active_hosts = array();
$hostbymac = array();
foreach($hostdata['Item'] as $hd ){
    if($hd['Active'] > 0){
        $active_hosts[] = $hd;
        $mac = $hd['MACAddress'];
        $hostbymac[$mac] = $hd;


    }
}

/**
 * Nodes ad n4t fill
 */
$nodes = array();
$n4t = array();
foreach($meshdata['nodes'] as $node){
    $nodes[$node['uid']] = CleanNode($node);
    $n4t[$node['uid']] = CleanNodeExtreme($node);
}

/**
 * Nodes clean...still require???
 */
foreach ($nodes as &$node) {
    if (!isset($node['node_interfaces'])) continue;

    foreach ($node['node_interfaces'] as &$interface) {
        if (!isset($interface['node_links'])) continue;

        // Keep only node_links with state == 'CONNECTED'
        $interface['node_links'] = array_filter(
            $interface['node_links'],
            function ($link) {
                return isset($link['state']) && $link['state'] === 'CONNECTED';
            }
        );
    }
}
unset($node, $interface); // Good practice to unset references




/**
 * Nodetree from array
 */
$todel = array();
$nodetree = buildNodeTreeSafe($n4t);
for($x = 0; $x < count ($nodetree['forest']);$x++){
    $nlinknum=0;
    for($y = 0; $y < count ($nodetree['forest'][$x]['node_interfaces']); $y++){
        $nlinknum += count($nodetree['forest'][$x]['node_interfaces'][$y]['node_links']);
    }
    unset($nodetree['forest'][$x]['node_interfaces']);  
    if($nlinknum < 1){
        $todel[] = $x;
       // unset($nodetree['forest'][$x]);
    }
}
removeEmptyChildren($nodetree);
removeNodeIterfaces($nodetree);
//Cleanup abandones meshs
if(count($todel)>0){
    rsort($todel);
    foreach($todel as $del){
        unset($nodetree['forest'][$del]);
    }
}
unset($nodetree['skipped_links']);

/**
 * Output
 */



$out = [
    'nodes' => $nodes
];


$out = [
    'nodetree' => $nodetree
];



print_r($out);


$outfile = __DIR__ . "/tree.html";

//$tmpx = NodeTable($nodetree['forest']);
//file_put_contents($outfile,$tmpx);


function NodeTable($nodetree){
    print_r(["Nodetree_given" => $nodetree]);
    $tbstart = "<table border='1'>";
    $tbend = "</table>";

    $out = $tbstart;
    for($x = 0; $x < count ($nodetree);$x++){
    //print_r(['nodetree_' . $x => $nodetree[$x]]);
        $out .= "<tr>";
            $out .= "<td>" ;
            $out .= "--" . $nodetree[$x]['uid'];
            if(isset( $nodetree[$x]['children'])){
                foreach($nodetree[$x]['children'] as $child){
                    $out .= NodeTable($child);
                }
            }
            


            $out .= "</td>";
        $out .= "</tr>";
    }    

    $out .= $tbend;

    return $out;


}






