<?php
session_start();
header('Content-Type: application/json');

$user = $_SESSION['email'] ?? null;
$role = $_SESSION['role'] ?? 'guest';
if (!$user) { http_response_code(401); echo json_encode(["error"=>"Not logged in"]); exit; }

$file = 'threads.json';
$threads = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

$action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD']==='POST' ? 'create' : 'list');
$threadId = intval($_REQUEST['threadId'] ?? 0);

if($action === 'list'){
    foreach($threads as &$t){
        $t['canDelete'] = ($t['author'] === $user || $role==='admin');
        $t['canEdit'] = ($t['author'] === $user || $role==='admin');
        foreach($t['comments'] ?? [] as &$c){
            $c['canEdit'] = ($c['author'] === $user || $role==='admin');
            $c['canDelete'] = ($c['author'] === $user || $role==='admin');
        }
        $t['comments'] = $t['comments'] ?? [];
    }
    echo json_encode($threads);
    exit;
}

if($action === 'create'){
    $title = trim($_POST['title'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    if(!$title || !$msg){ http_response_code(400); echo json_encode(['error'=>'Missing title/message']); exit; }

    $newThread = [
        'id' => time(),
        'title' => $title,
        'author' => $user,
        'date' => date('Y-m-d'),
        'comments' => [['author'=>$user,'text'=>$msg,'date'=>date('Y-m-d'),'canEdit'=>true,'canDelete'=>true]]
    ];
    $threads[] = $newThread;
    file_put_contents($file,json_encode($threads));
    echo json_encode(['success'=>true]);
    exit;
}

if($action === 'delete'){
    foreach($threads as $k=>$t){
        if($t['id'] === $threadId){
            if($t['author']===$user || $role==='admin'){
                array_splice($threads,$k,1);
                file_put_contents($file,json_encode($threads));
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(403);
                echo json_encode(['error'=>'Permission denied']);
            }
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['error'=>'Thread not found']);
    exit;
}

if($action === 'editThread'){
    $newTitle = trim($_POST['title'] ?? '');
    $newMsg = trim($_POST['message'] ?? '');
    if(!$newTitle || !$newMsg){ http_response_code(400); exit; }

    foreach($threads as &$t){
        if($t['id'] === $threadId){
            if($t['author'] === $user || $role === 'admin'){
                $t['title'] = $newTitle;
                if(isset($t['comments'][0])) $t['comments'][0]['text'] = $newMsg;
                file_put_contents($file,json_encode($threads));
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(403);
                echo json_encode(['error'=>'Permission denied']);
            }
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['error'=>'Thread not found']);
    exit;
}

if($action === 'addComment'){
    $text = trim($_POST['text'] ?? '');
    if(!$text){ http_response_code(400); exit; }

    foreach($threads as &$t){
        if($t['id'] === $threadId){
            $t['comments'][] = ['author'=>$user,'text'=>$text,'date'=>date('Y-m-d')];
            file_put_contents($file,json_encode($threads));
            echo json_encode(['success'=>true]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['error'=>'Thread not found']);
    exit;
}

if($action === 'deleteComment'){
    $commentIndex = intval($_GET['commentId'] ?? -1);
    foreach($threads as &$t){
        if($t['id'] === $threadId){
            if(!isset($t['comments'][$commentIndex])){
                http_response_code(404);
                exit;
            }
            $c = $t['comments'][$commentIndex];
            if($c['author']!==$user && $role!=='admin'){
                http_response_code(403);
                exit;
            }
            array_splice($t['comments'],$commentIndex,1);
            file_put_contents($file,json_encode($threads));
            echo json_encode(['success'=>true]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['error'=>'Thread not found']);
    exit;
}

if($action === 'editComment'){
    $commentIndex = intval($_GET['commentId'] ?? -1);
    $newText = trim($_POST['text'] ?? '');
    if(!$newText){ http_response_code(400); exit; }

    foreach($threads as &$t){
        if($t['id'] === $threadId){
            if(!isset($t['comments'][$commentIndex])){
                http_response_code(404);
                exit;
            }
            $c = &$t['comments'][$commentIndex];
            if($c['author'] === $user || $role === 'admin'){
                $c['text'] = $newText;
                file_put_contents($file,json_encode($threads));
                echo json_encode(['success'=>true]);
            } else {
                http_response_code(403);
                echo json_encode(['error'=>'Permission denied']);
            }
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['error'=>'Thread not found']);
    exit;
}
?>
