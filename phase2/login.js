document.getElementById("loginForm").addEventListener("submit", function(e){
  const email = document.getElementById("loginEmail").value.trim().toLowerCase();
  const pw = document.getElementById("loginPass").value.trim();

  if(!email || !pw){
    e.preventDefault(); 
    alert("Please enter email and password.");
    return;
  }

  if(!email.endsWith("@stu.uob.edu.bh") && !email.endsWith("@uob.edu.bh")){
    e.preventDefault();
    alert("Invalid email domain!");
  }
});
