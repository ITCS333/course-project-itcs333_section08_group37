async function loadThreads() {
    const response = await fetch("Task5.php?action=list");
    const threads = await response.json();
    renderThreads(threads);
}


function renderThreads(threads) {
    const container = document.getElementById("threads");
    container.innerHTML = "";

    threads.forEach(t => {
        const div = document.createElement("div");
        div.className = "resource-item";

        div.innerHTML = `
            <h3>${t.title}
                <span class="small" style="float:right">${t.date}</span>
            </h3>

            <p class="small">Posted by ${t.author}</p>
            <p>${t.comments} replies</p>

            <div style="display:flex;gap:8px;margin-top:8px;">

                <button class="btn"
                    onclick="viewThread(${t.id})">View</button>

                ${t.canDelete ? `
                    <button class="btn btn-danger"
                        onclick="deleteThread(${t.id})">
                        Delete
                    </button>` : ""}
            </div>
        `;

        container.appendChild(div);
    });
}


function viewThread(id) {
    window.location.href = "threadView.php?id=" + id;
}


document.getElementById("createThread").addEventListener("click", async () => {

    const title = document.getElementById("threadTitle").value.trim();
    const msg = document.getElementById("threadMsg").value.trim();

    if (!title || !msg) {
        alert("Title and message are required.");
        return;
    }

    const form = document.getElementById("newThreadForm");
    const data = new FormData(form);

    const response = await fetch("Task5.php", {
        method: "POST",
        body: data
    });

    if (response.ok) {
        form.reset();
        loadThreads();
    } else {
        alert("Error Occurred");
    }
});


async function deleteThread(id) {
    if (!confirm("Delete the thread?")) return;

    const res = await fetch("Task5.php?action=delete&id=" + id);

    if (res.ok) {
        loadThreads();
    } else {
        alert("No permission to delete the thread.");
    }
}


loadThreads();
