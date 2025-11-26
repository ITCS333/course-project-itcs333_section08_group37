let currentUser = "<?php echo $_SESSION['email'] ?? 'guest'; ?>";
let role = "<?php echo $_SESSION['role'] ?? 'guest'; ?>";

async function loadThreads() {
    const res = await fetch("Task5.php?action=list");
    const threads = await res.json();
    renderThreads(threads);
}

function renderThreads(threads){
    const container = document.getElementById("threads");
    container.innerHTML = "";

    threads.forEach((t, tidx)=>{
        const div = document.createElement("div");
        div.className = "resource-item";

        div.innerHTML = `
            <h3>${t.title} <span class="small" style="float:right">${t.date}</span></h3>
            <p class="small">Posted by ${t.author}</p>
            <p>${t.comments.length} replies</p>
            <div style="display:flex;gap:8px;margin-top:8px;">
                <button class="btn" onclick="viewThread(${t.id})">View</button>
                ${t.canEdit ? `<button class="btn btn-secondary" onclick="editThread(${t.id})">Edit</button>` : ''}
                ${t.canDelete ? `<button class="btn btn-danger" onclick="deleteThread(${t.id})">Delete</button>` : ''}
            </div>
        `;
        container.appendChild(div);
    });
}

function viewThread(id){
    window.location.href = "threadView.php?id=" + id;
}

document.getElementById("createThread").addEventListener("click", async ()=>{
    const title = document.getElementById("threadTitle").value.trim();
    const msg = document.getElementById("threadMsg").value.trim();
    if(!title||!msg){ alert("Title and message required"); return; }

    const form = new FormData(document.getElementById("newThreadForm"));
    const res = await fetch("Task5.php", {method:"POST", body:form});
    if(res.ok){ document.getElementById("newThreadForm").reset(); loadThreads(); }
});

async function deleteThread(id){
    if(!confirm("Delete this topic?")) return;
    const res = await fetch(`Task5.php?action=delete&id=${id}`);
    if(res.ok) loadThreads(); else alert("Permission Denied");
}

async function editThread(id){
    const newTitle = prompt("New topic title:");
    const newMsg = prompt("New message:");
    if(!newTitle||!newMsg) return;

    const form = new FormData();
    form.append("title", newTitle);
    form.append("message", newMsg);

    const res = await fetch(`Task5.php?action=editThread&id=${id}`, {method:"POST", body:form});
    if(res.ok) loadThreads(); else alert("Permission Denied");
}


window.postComment = async (threadId, text)=>{
    if(!text.trim()) return;
    const form = new FormData();
    form.append("text", text);
    form.append("threadId", threadId);
    await fetch("Task5.php?action=addComment", {method:"POST", body:form});
    loadThreads();
}

window.deleteComment = async (threadId, commentIndex)=>{
    if(!confirm("Delete this comment?")) return;
    const res = await fetch(`Task5.php?action=deleteComment&threadId=${threadId}&commentId=${commentIndex}`);
    if(res.ok) loadThreads(); else alert("Permission Denied");
}

window.editComment = async (threadId, commentIndex)=>{
    const newText = prompt("Edit your comment:");
    if(!newText) return;
    const form = new FormData();
    form.append("text", newText);
    const res = await fetch(`Task5.php?action=editComment&threadId=${threadId}&commentId=${commentIndex}`, {method:"POST", body:form});
    if(res.ok) loadThreads(); else alert("Permission Denied");
}

loadThreads();
