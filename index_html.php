<?php


require_once( __DIR__  . "/vendor/autoload.php");

require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/func.php");


use blacksenator\fritzsoap\hosts;

$makeclean = true;

$lifecall = false;


/*
$tfile['mesh'] = __DIR__ . "/_mesh_cache";
$tfile['host'] = __DIR__ . "/_host_cache";

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

$active_hosts = array();
$hostbymac = array();
foreach($hostdata['Item'] as $hd ){
    if($hd['Active'] > 0){
        $active_hosts[] = $hd;
        $mac = $hd['MACAddress'];
        $hostbymac[$mac] = $hd;
        if(isset($hd['X_AVM-DE_MACAddressList'])){
                $altmacs = explode(',',$hd['X_AVM-DE_MACAddressList']);
                foreach($altmacs as $altmac){
                    $hostbymac[$altmac] = $hd;
                }
        }
    }
}


    $n4t = array();
    $nodes = array();
    foreach($meshdata['nodes'] as $node){
        $nodes[$node['uid']] = $node;
        $n4t[$node['uid']] = CleanNodeExtreme($node);
    }

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



    $children = [];
    foreach ($nodes as $uid => $node) {
        if (!isset($node['node_interfaces']) || !is_array($node['node_interfaces'])) continue;
        foreach ($node['node_interfaces'] as $iface) {
            if (!isset($iface['node_links']) || !is_array($iface['node_links'])) continue;
            foreach ($iface['node_links'] as $link) {
                if (isset($link['node_1_uid'], $link['node_2_uid'])) {
                    $p = $link['node_1_uid'];
                    $c = $link['node_2_uid'];
                    $children[$p][] = $c;
                }
            }
        }
    }

    foreach ($children as &$parent){    
        $parent= array_unique($parent);
    }


*/

GetFritzData(false);














function renderAdjacencyTree(array $tree, string $root = 'n-1', array &$visited = []) {
    // prevent infinite recursion (cycles)
    
    if (in_array($root, $visited, true)) {
        return "";
        return '<div class="node"><div class="box loop">'
             . htmlspecialchars($root)
             . '<br><small>↺ loop</small></div></div>';
    }
    
    $visited[] = $root;

    $html = '<div class="node">';
    $html .= '<div class="box">' . GetNodeBadge($root) . '</div>';

    // Render children if exist
    if (isset($tree[$root]) && is_array($tree[$root]) && count($tree[$root]) > 0) {
        $html .= '<div class="children">';
        foreach ($tree[$root] as $child) {
            $html .= renderAdjacencyTree($tree, $child, $visited);
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Adjacency Tree Chart</title>
<style>
body {
    font-family: sans-serif;
    background: #fafafa;
    padding: 30px;
    text-align: center;
}
.tree {
    display: inline-block;
    text-align: center;
}
.node {
    position: relative;
    display: inline-block;
    vertical-align: top;
    margin: 20px 10px;
}
.box {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid #ccc;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.box.loop {
    background: #ffeaea;
    border-color: #ff8c8c;
}
.node::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    border-left: 2px solid #ccc;
    height: 20px;
    transform: translateY(-20px);
    display: none;
}
.children {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    position: relative;
}
.children::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 10%;
    right: 10%;
    height: 2px;
    background: #ccc;
}
.children > .node::before {
    display: block;
}
.children > .node {
    margin: 0 15px;
}
</style>
</head>
<body>
<h2>PHP Tree Chart (Adjacency List)</h2>
<div class="tree">
    <?php echo renderAdjacencyTree($children, 'n-1'); ?>
</div>
<?php
echo "<pre>" . print_r($children,true) . "</pre>";
echo "<pre>" . print_r($hostbymac,true) . "</pre>";


?>
</body>
</html>
