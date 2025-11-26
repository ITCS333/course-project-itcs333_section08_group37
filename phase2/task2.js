
const role = localStorage.getItem("role") || "student";
document.getElementById("roleDisplay").textContent =
  role === "admin" ? "Admin Dashboard" : "Student Dashboard";

document.querySelectorAll(".admin-only").forEach(el => {
  el.style.display = (role === "admin") ? "block" : "none";
});


async function loadResources() {
  const res = await fetch('task2.php?action=get_resources');
  const data = await res.json();
  const container = document.getElementById("resourcesContainer");
  container.innerHTML = "<h2>Course Resources</h2>";

  data.forEach(r => {
    const div = document.createElement("div");
    div.className = "card resource-item";
    div.innerHTML = `
      <h3>${r.title}</h3>
      <p>${r.description}</p>
      <ul>${r.links.map(l=>`<li><a href="${l.link_url}" target="_blank">${l.link_name}</a></li>`).join('')}</ul>
      ${role==='admin'?`<div class="admin-only">
        <button class="btn small edit-btn" data-id="${r.id}">Edit</button>
        <button class="btn small danger delete-btn" data-id="${r.id}">Delete</button>
      </div>`:''}
      <label>Discussion:</label>
      <textarea placeholder="Write your comment..."></textarea>
      <button class="btn small comment-btn" data-id="${r.id}">Submit</button>
    `;
    container.appendChild(div);
  });

  attachEvents();
}

function attachEvents() {
  
  document.querySelectorAll(".comment-btn").forEach(btn=>{
    btn.addEventListener("click", async ()=>{
      const id = btn.dataset.id;
      const comment = btn.previousElementSibling.value.trim();
      if(!comment) return alert("Write your comment!");
      await fetch('task2.php?action=add_comment', {
        method:'POST',
        body: JSON.stringify({resource_id:id, comment}),
        headers: {'Content-Type':'application/json'}
      });
      alert("Comment added!");
      btn.previousElementSibling.value="";
    });
  });

  if(role==='admin'){
    
    document.querySelectorAll(".delete-btn").forEach(btn=>{
      btn.addEventListener("click", async ()=>{
        if(!confirm("Are you sure?")) return;
        const id = btn.dataset.id;
        await fetch('task2.php?action=delete_resource', {
          method:'POST',
          body: JSON.stringify({resource_id:id}),
          headers:{'Content-Type':'application/json'}
        });
        loadResources();
      });
    });

    
    document.querySelectorAll(".edit-btn").forEach(btn=>{
      btn.addEventListener("click", async ()=>{
        const id = btn.dataset.id;
        const title = prompt("New title:");
        const desc = prompt("New description:");
        if(title && desc){
          await fetch('task2_api.php?action=edit_resource', {
            method:'POST',
            body: JSON.stringify({resource_id:id,title,description:desc}),
            headers:{'Content-Type':'application/json'}
          });
          loadResources();
        }
      });
    });

    
    document.getElementById("addResourceBtn").addEventListener("click", async ()=>{
      const title = prompt("Resource title:");
      const desc = prompt("Resource description:");
      if(title && desc){
        await fetch('task2.php?action=add_resource',{
          method:'POST',
          body:JSON.stringify({title,description:desc}),
          headers:{'Content-Type':'application/json'}
        });
        loadResources();
      }
    });
  }
}


function logout(){
  localStorage.removeItem("role");
  window.location.href="Task1.html";
}


loadResources();
