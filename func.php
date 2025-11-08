<?php 


/**
 * Build a tree/forest from flat $nodes while preventing infinite loops.
 *
 * @param array $nodes Associative array keyed by uid (e.g. 'n-1' => [...])
 * @return array ['forest' => [...], 'skipped_links' => [...]]
 */
function buildNodeTreeSafe(array $nodes): array {
    // 1) Build adjacency list parent => [children...]
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

    // 2) Find nodes that appear as children
    $isChild = [];
    foreach ($children as $p => $list) {
        foreach ($list as $c) $isChild[$c] = true;
    }

    // 3) Choose roots: nodes that are not children of anyone. If none, treat all nodes as possible roots.
    $roots = [];
    foreach ($nodes as $uid => $_) {
        if (!isset($isChild[$uid])) $roots[] = $uid;
    }
    if (empty($roots)) {
        // fallback: use all node keys (graph might be a cycle with no standalone root)
        $roots = array_keys($nodes);
    }

    // 4) Build trees safely with visited set; record skipped links that would create a cycle
    $skipped_links = [];
    $globalVisited = []; // prevents the same node appearing in multiple places in the forest

    $build = function(string $uid) use (&$build, &$nodes, &$children, &$globalVisited, &$skipped_links) {
        $nodeCopy = $nodes[$uid] ?? ['uid' => $uid]; // keep original data if exists
        $nodeCopy['children'] = [];

        // mark visited globally so we don't put same node into multiple places
        $globalVisited[$uid] = true;

        if (!empty($children[$uid])) {
            foreach ($children[$uid] as $childUid) {
                if (!isset($nodes[$childUid])) {
                    // child referenced but node data missing — include as placeholder
                    $nodeCopy['children'][] = [
                        'uid' => $childUid,
                        '_missing_node' => true
                    ];
                    continue;
                }

                if (isset($globalVisited[$childUid])) {
                    // child already used higher in the forest -> would form cycle / duplicate: skip and note it
                    $skipped_links[] = [
                        'from' => $uid,
                        'to'   => $childUid,
                        'reason' => 'would-create-cycle-or-duplicate'
                    ];
                    // optionally include a placeholder so user sees there's a link but it was skipped
                    /*
                    $nodeCopy['children'][] = [
                        'uid' => $childUid,
                        '_skipped_cycle' => true
                    ];
                    */
                } else {
                    $nodeCopy['children'][] = $build($childUid);
                }
            }
        }

        return $nodeCopy;
    };

    // 5) Build forest
    $forest = [];
    foreach ($roots as $r) {
        if (!isset($nodes[$r])) {
            // ignore unknown root entry
            continue;
        }
        if (isset($globalVisited[$r])) {
            // root already consumed when building another root (rare) — skip
            continue;
        }
        $forest[] = $build($r);
    }

    return [
        'forest' => $forest,
        'skipped_links' => $skipped_links
    ];
}




function CleanNode($nodegiven){
        global $makeclean;
        //if(!$makeclean ) return $nodegiven;
        $node = $nodegiven;
        
        //if($node['is_meshed'] != 1){
            if(isset($node['mesh_role'])) unset($node['mesh_role']);
            if(isset($node['meshd_version'])) unset($node['meshd_version']);
            if(isset($node['device_id'])) unset($node['device_id']);
            if(isset($node['device_capabilities'])) unset($node['device_capabilities']);
            if(isset($node['enabled_device_capabilities'])) unset($node['enabled_device_capabilities']);
        //}
            if(isset($node['metrics'])) unset($node['metrics']);
            //if(isset($node[''])) unset($node['']);
            //if(isset($node[''])) unset($node['']);            
            for($x = 0; $x < count($node['node_interfaces']); $x++){
                
                    $nodecatchstr = "supported_streams_tx,security,opmode,dl_ttlm_ul_ttlm_link_id,supported_streams_rx,current_channel,current_channel_info,phymodes,channel_utilization,anpi,steering_enabled,11k_friendly,11v_friendly,legacy_friendly,rrm_compliant,mlo_modes,channel_list,radio_id,link_detected,cur_data_rate,cur_availability_rx,cur_availability_tx,lldp_active,mld_mac_address,link_id,ul_ttlm,dl_ttlm,ssid,device_vendor_class_id,device_model,device_manufacturer,device_firmware_version,blocking_state,device_id,device_capabilities,enabled_device_capabilities";
                    //$nodecatchstr = "aaaa,bbbb";
                    $nodecatcharr = explode(",",$nodecatchstr);
                    foreach($nodecatcharr as $nodecatch){
                        if(array_key_exists($nodecatch,$node['node_interfaces'][$x])) unset($node['node_interfaces'][$x][$nodecatch]);
                        
                    }



                    for($y = 0 ; $y < count( $node['node_interfaces'][$x]['node_links'] );$y++){
                            // $node['node_interfaces'][$x]['node_links'][$y]
                        $catchworkstr = "max_data_rate_rx,max_data_rate_tx,cur_data_rate_rx,cur_data_rate_tx,cur_availability_rx,cur_availability_tx,learned_via_lldp,rx_rsni,tx_rsni,rx_rcpi,tx_rcpi,mld_mac_address,link_id,ul_ttlm,dl_ttlm,ssid,device_vendor_class_id,device_model,device_manufacturer,device_firmware_version,device_id,device_capabilities,enabled_device_capabilities";
                        $catchwork = explode(",",$catchworkstr);
                        //                        $catchwork = ["max_data_rate_rx","max_data_rate_tx","cur_data_rate_rx","cur_data_rate_tx","cur_availability_rx","cur_availability_tx","learned_via_lldp","rx_rsni","tx_rsni","rx_rcpi","tx_rcpi" ];

                        foreach($catchwork as $tf){
                            if(array_key_exists( $tf, $node['node_interfaces'][$x]['node_links'][$y]  )) unset ($node['node_interfaces'][$x]['node_links'][$y][$tf]);
                        }
                    }
            }
        return $node;
}



function CleanNodeExtreme($nodegiven){
        global $makeclean;
        //if(!$makeclean ) return $nodegiven;
        $node = $nodegiven;
                    $nodecatchstr = "device_capabilities,device_firmware_version,device_friendly_name,device_id,device_mac_address,device_manufacturer,device_model,device_name,device_vendor_class_id,enabled_device_capabilities,is_meshed,last_connected,mac_address,name,node_interface_1_uid,node_interface_2_uid,node_uid,state,type,supported_streams_tx,security,opmode,dl_ttlm_ul_ttlm_link_id,supported_streams_rx,current_channel,current_channel_info,phymodes,channel_utilization,anpi,steering_enabled,11k_friendly,11v_friendly,legacy_friendly,rrm_compliant,mlo_modes,channel_list,radio_id,link_detected,cur_data_rate,cur_availability_rx,cur_availability_tx,lldp_active,mld_mac_address,link_id,ul_ttlm,dl_ttlm,ssid,rx_rsni,tx_rsni,rx_rcpi,tx_rcpi,blocking_state,metrics,mesh_role,meshd_version";
                    //$nodecatchstr = "aaaa,bbbb";
                    $nodecatcharr = explode(",",$nodecatchstr);        
        //if($node['is_meshed'] != 1){

            foreach($nodecatcharr as $nodecatch){
                if(array_key_exists($nodecatch,$node)) unset($node[$nodecatch]);
                
            }


            //if(isset($node[''])) unset($node['']);
            //if(isset($node[''])) unset($node['']);            
            for($x = 0; $x < count($node['node_interfaces']); $x++){
                

                    foreach($nodecatcharr as $nodecatch){
                        if(array_key_exists($nodecatch,$node['node_interfaces'][$x])) unset($node['node_interfaces'][$x][$nodecatch]);
                        
                    }



                    for($y = 0 ; $y < count( $node['node_interfaces'][$x]['node_links'] );$y++){
                            // $node['node_interfaces'][$x]['node_links'][$y]
                        $catchworkstr = "device_capabilities,device_firmware_version,device_friendly_name,device_id,device_mac_address,device_manufacturer,device_model,device_name,device_vendor_class_id,enabled_device_capabilities,is_meshed,last_connected,mac_address,name,node_interface_1_uid,node_interface_2_uid,node_uid,state,type,supported_streams_tx,security,opmode,dl_ttlm_ul_ttlm_link_id,supported_streams_rx,current_channel,current_channel_info,phymodes,channel_utilization,anpi,steering_enabled,11k_friendly,11v_friendly,legacy_friendly,rrm_compliant,mlo_modes,channel_list,radio_id,link_detected,cur_data_rate,cur_data_rate_rx,cur_data_rate_tx,cur_availability_rx,cur_availability_tx,learned_via_lldp,lldp_active,,max_data_rate_rx,max_data_rate_tx,mld_mac_address,link_id,ul_ttlm,dl_ttlm,ssid,rx_rsni,tx_rsni,rx_rcpi,tx_rcpi,blocking_state,metrics,,mesh_role,meshd_version";
                        $catchwork = explode(",",$catchworkstr);
                        //                        $catchwork = ["max_data_rate_rx","max_data_rate_tx","cur_data_rate_rx","cur_data_rate_tx","cur_availability_rx","cur_availability_tx","learned_via_lldp","rx_rsni","tx_rsni","rx_rcpi","tx_rcpi" ];

                        foreach($catchwork as $tf){
                            if(array_key_exists( $tf, $node['node_interfaces'][$x]['node_links'][$y]  )) unset ($node['node_interfaces'][$x]['node_links'][$y][$tf]);
                        }
                    }
            }
        return $node;
}


function removeEmptyChildren(array &$array) {
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            // Recurse into sub-arrays
            removeEmptyChildren($value);
        }
    }

    // If this array has a 'children' key and it's empty, remove it
    if (isset($array['children']) && empty($array['children'])) {
        unset($array['children']);
    }
}

//node_interfaces

function removeNodeIterfaces(array &$array) {
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            // Recurse into sub-arrays
            removeNodeIterfaces($value);
        }
    }

    // If this array has a 'children' key and it's empty, remove it
    if (isset($array['node_interfaces'])) {
        unset($array['node_interfaces']);
    }
}




function GetNodeBadge($nodename){
    global $nodes;
    $tn = $nodes[$nodename];
    $out = $nodename . "<br>";
    $out .= $tn['device_name'];

 //   $out .= print_r($tn,true);
    return $out;
}




function renderTreeChart(array $treeArray) {
    if (!isset($treeArray['forest'])) {
        return '<p>No valid tree structure found.</p>';
    }

    // Recursive renderer for nodes
    $renderNode = function($node) use (&$renderNode) {
        $html = '<div class="node">';
//        $html .= '<div class="box">' . htmlspecialchars($node['uid']) . "<br>Und<br>ein<br>bisschen extra text" . '</div>';
        $html .= '<div class="box">' . GetNodeBadge($node['uid']) . '</div>';
        if (!empty($node['children']) && is_array($node['children'])) {
            $html .= '<div class="children">';
            foreach ($node['children'] as $child) {
                $html .= $renderNode($child);
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    };

    // Wrap forest roots
    $html = '<div class="tree">';
    foreach ($treeArray['forest'] as $root) {
        $html .= $renderNode($root);
    }
    $html .= '</div>';

    // Include chart CSS
    $html .= <<<STYLE
<style>
.tree {
    display: inline-block;
    text-align: center;
    margin: 40px;
    font-family: sans-serif;
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
    border: 1px solid #ccc;
    border-radius: 8px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
STYLE;

    return $html;
}