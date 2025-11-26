async function loadThreads() {
    const response = await fetch("Task5.php?action=list");
    const data = await response.json();
    renderThreads(data);
}

function renderThreads(threads) {
    const div = document.getElementById("threads");
    div.innerHTML = "";

    threads.forEach(t => {
        const box = document.createElement("div");
        box.className = "resource-item";

        box.innerHTML = `
            <h3>${t.title} <span class="small" style="float:right">${t.date}</span></h3>
            <p class="small">Posted by ${t.author}</p>
            <p>${t.comments} replies</p>

            <div style="display:flex;gap:8px;margin-top:8px;">
                <button class="btn" onclick="openThread(${t.id})">View</button>

                ${t.canDelete ? 
                    `<button class="btn btn-danger" onclick="deleteThread(${t.id})">Delete</button>` 
                : ""}
            </div>
        `;

        div.appendChild(box);
    });
}


function openThread(id) {
    window.location = "Task5.php?thread=" + id;
}


document.getElementById("createThread").addEventListener("click", async () => {
    const title = threadTitle.value.trim();
    const msg = threadMsg.value.trim();

    if (!title || !msg) return alert("Title and message required");

    await fetch("Task5.php", {
        method: "POST",
        body: new FormData(document.getElementById("newThreadForm"))
    });

    threadTitle.value = "";
    threadMsg.value = "";

    loadThreads();
});


async function deleteThread(id) {
    if (!confirm("Delete thread?")) return;

    await fetch("Task5.php?action=delete&id=" + id);
    loadThreads();
}

loadThreads();
