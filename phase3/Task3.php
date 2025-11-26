<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Weekly Details</title>

 
  <link rel="stylesheet" href="style.css">

 
  <script src="Task3.js" defer></script>
</head>

<body>
  <header>
    <h1>Week Details</h1>

  
    <div>
      <a class="nav-link" href="Task1.html">Home</a>
      <a class="nav-link" href="Task2333.html">Resources</a>
      <a class="nav-link" href="Task3.html">Weekly Plan</a>
      <a class="nav-link" href="Task4333.html">Assignments</a>
      <a class="nav-link" href="Task5333.html">Discussion</a>

      <span id="roleDisplay" class="role-label"></span>
      <button class="btn" onclick="logout()">Logout</button>
    </div>
  </header>

  <main class="container">

  
    <section class="week-details">
      <h2>Weekly Plan</h2>
      <p class="description">This page contains week information for IT courses.</p>

      <h3>Course List</h3>
      <ul>
     
        <li>ITCS113</li>
        <li>ITCS114</li>
        <li>ITCS214</li>
        <li>ITCS285</li>
        <li>ITCS333</li>
        <li>ITCS444</li>
        <li>ITCS113, ITCS114, ITCS214, ITCS285, ITCS333, ITCS444.</li>
      </ul>
    </section>

  
    <section class="comments-section">
      <h3>Discussion / Comments</h3>
      <div class="comment-box">
        <textarea placeholder="Write a comment..."></textarea>
        <button class="btn" onclick="submitComment()">Submit</button>
      </div>

      <div class="comments-list">
        <div class="comment">
          <p><strong>Student A:</strong> This week was helpful!</p>
        </div>
        <div class="comment">
          <p><strong>Student B:</strong> I need clarification on the second part.</p>
        </div>
      </div>
    </section>

  </main>

</body>
</html>
