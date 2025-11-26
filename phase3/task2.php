<?php
session_start();
header('Content-Type: application/json');

$pdo = new PDO("mysql:host=localhost;dbname=itcs333_project;charset=utf8", "root", "");

$action = $_GET['action'] ?? '';

if($action==='get_resources'){
    $stmt = $pdo->query("SELECT * FROM resources ORDER BY created_at DESC");
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($resources as &$r){
        $stmt2 = $pdo->prepare("SELECT * FROM resource_links WHERE resource_id=?");
        $stmt2->execute([$r['id']]);
        $r['links'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode($resources);
    exit;
}

$data = json_decode(file_get_contents('php://input'),true);
$userRole = $_SESSION['role'] ?? 'student';
$userId = $_SESSION['user_id'] ?? 0;

if($action==='add_comment'){
    $stmt = $pdo->prepare("INSERT INTO comments(resource_id,user_id,comment) VALUES(?,?,?)");
    $stmt->execute([$data['resource_id'],$userId,$data['comment']]);
    echo json_encode(['success'=>true]);
    exit;
}

if($userRole!=='admin'){ echo json_encode(['error'=>'Access denied']); exit; }

if($action==='delete_resource'){
    $stmt = $pdo->prepare("DELETE FROM resources WHERE id=?");
    $stmt->execute([$data['resource_id']]);
    echo json_encode(['success'=>true]);
    exit;
}

if($action==='edit_resource'){
    $stmt = $pdo->prepare("UPDATE resources SET title=?,description=? WHERE id=?");
    $stmt->execute([$data['title'],$data['description'],$data['resource_id']]);
    echo json_encode(['success'=>true]);
    exit;
}

if($action==='add_resource'){
    $stmt = $pdo->prepare("INSERT INTO resources(title,description,created_at) VALUES(?,?,NOW())");
    $stmt->execute([$data['title'],$data['description']]);
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['error'=>'Invalid action']);
