/*
  Requirement: Make the "Discussion Board" page interactive.

  Instructions:
  1. Link this file to `board.html` (or `baord.html`) using:
     <script src="board.js" defer></script>
  
  2. In `board.html`, add an `id="topic-list-container"` to the 'div'
     that holds the list of topic articles.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
// TODO: Select the new topic form ('#new-topic-form').

// TODO: Select the topic list container ('#topic-list-container').

// --- Functions ---

/**
 * TODO: Implement the createTopicArticle function.
 * It takes one topic object {id, subject, author, date}.
 * It should return an <article> element matching the structure in `board.html`.
 * - The main link's `href` MUST be `topic.html?id=${id}`.
 * - The footer should contain the author and date.
 * - The actions div should contain an "Edit" button and a "Delete" button.
 * - The "Delete" button should have a class "delete-btn" and `data-id="${id}"`.
 */
function createTopicArticle(topic) {
  // ... your implementation here ...
}

/**
 * TODO: Implement the renderTopics function.
 * It should:
 * 1. Clear the `topicListContainer`.
 * 2. Loop through the global `topics` array.
 * 3. For each topic, call `createTopicArticle()`, and
 * append the resulting <article> to `topicListContainer`.
 */
function renderTopics() {
  // ... your implementation here ...
}

/**
 * TODO: Implement the handleCreateTopic function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the '#topic-subject' and '#topic-message' inputs.
 * 3. Create a new topic object with the structure:
 * {
 * id: `topic_${Date.now()}`,
 * subject: (subject value),
 * message: (message value),
 * author: 'Student' (use a hardcoded author for this exercise),
 * date: new Date().toISOString().split('T')[0] // Gets today's date YYYY-MM-DD
 * }
 * 4. Add this new topic object to the global `topics` array (in-memory only).
 * 5. Call `renderTopics()` to refresh the list.
 * 6. Reset the form.
 */
function handleCreateTopic(event) {
  // ... your implementation here ...
}

/**
 * TODO: Implement the handleTopicListClick function.
 * This is an event listener on the `topicListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `topics` array by filtering out the topic
 * with the matching ID (in-memory only).
 * 4. Call `renderTopics()` to refresh the list.
 */
function handleTopicListClick(event) {
  // ... your implementation here ...
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'topics.json'.
 * 2. Parse the JSON response and store the result in the global `topics` array.
 * 3. Call `renderTopics()` to populate the list for the first time.
 * 4. Add the 'submit' event listener to `newTopicForm` (calls `handleCreateTopic`).
 * 5. Add the 'click' event listener to `topicListContainer` (calls `handleTopicListClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
/*
  Requirement: Make the "Discussion Board" page interactive.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
// TODO: Select the new topic form ('#new-topic-form').
const newTopicForm = document.getElementById('new-topic-form');

// TODO: Select the topic list container ('#topic-list-container').
const topicListContainer = document.getElementById('topic-list-container');

// --- Functions ---

/**
 * TODO: Implement the createTopicArticle function.
 */
function createTopicArticle(topic) {
  const article = document.createElement('article');
  article.className = 'topic-summary';
  article.setAttribute('data-id', topic.id);

  // The main link's href MUST be topic.html?id=${id}
  article.innerHTML = `
    <h3>
      <a href="topic.html?id=${topic.id}">${topic.subject}</a>
    </h3>
    <footer>
      <p>Posted by: <strong>${topic.author}</strong> on ${topic.date}</p>
    </footer>
    <div class="topic-message-preview">
        <p>${topic.message.substring(0, 100)}...</p>
    </div>
    <div class="topic-actions">
      <a href="#" class="edit-btn" data-id="${topic.id}">Edit</a>
      <button class="delete-btn" data-id="${topic.id}">Delete</button>
    </div>
  `;
  return article;
}

/**
 * TODO: Implement the renderTopics function.
 */
function renderTopics() {
  // 1. Clear the `topicListContainer`.
  topicListContainer.innerHTML = '';

  // Check for empty list
  if (topics.length === 0) {
      topicListContainer.innerHTML = '<p>No topics have been posted yet. Start the conversation!</p>';
      return;
  }
  
  // 2. Loop through the global `topics` array.
  // 3. For each topic, call `createTopicArticle()`, and append.
  topics.forEach(topic => {
    const topicEl = createTopicArticle(topic);
    topicListContainer.appendChild(topicEl);
  });
}

/**
 * TODO: Implement the handleCreateTopic function.
 */
function handleCreateTopic(event) {
  // 1. Prevent the form's default submission.
  event.preventDefault();

  // 2. Get the values from the '#topic-subject' and '#topic-message' inputs.
  const subjectInput = document.getElementById('topic-subject');
  const messageInput = document.getElementById('topic-message');
  const subject = subjectInput.value.trim();
  const message = messageInput.value.trim();

  if (!subject || !message) return;

  // 3. Create a new topic object
  const newTopic = {
    id: `topic_${Date.now()}`,
    subject: subject,
    message: message,
    author: 'Student', // hardcoded author
    date: new Date().toISOString().split('T')[0] // Gets today's date YYYY-MM-DD
  };

  // 4. Add this new topic object to the global `topics` array (in-memory only).
  topics.unshift(newTopic); // Add to the start so it appears first

  // 5. Call `renderTopics()` to refresh the list.
  renderTopics();

  // 6. Reset the form.
  newTopicForm.reset();
}

/**
 * TODO: Implement the handleTopicListClick function.
 */
function handleTopicListClick(event) {
  // 1. Check if the clicked element (`event.target`) has the class "delete-btn".
  if (event.target.classList.contains('delete-btn')) {
    // 2. If it does, get the `data-id` attribute from the button.
    const topicIdToDelete = event.target.getAttribute('data-id');
    
    if (!confirm(`Are you sure you want to delete topic ${topicIdToDelete}?`)) {
        return;
    }

    // 3. Update the global `topics` array by filtering out the topic
    topics = topics.filter(topic => topic.id !== topicIdToDelete);

    // (Bonus: In a real app, you would also need to filter comments/replies here)
    // Here, we remove the topic and its associated replies from the in-memory data.
    
    // 4. Call `renderTopics()` to refresh the list.
    renderTopics();
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 */
async function loadAndInitialize() {
  try {
    // 1. Use `fetch()` to get data from 'topics.json'.
    const response = await fetch('./api/topics.json');
    const data = await response.json();

    // 2. Parse the JSON response and store the result in the global `topics` array.
    topics = data;

    // 3. Call `renderTopics()` to populate the list for the first time.
    renderTopics();

    // 4. Add the 'submit' event listener to `newTopicForm`.
    if (newTopicForm) {
      newTopicForm.addEventListener('submit', handleCreateTopic);
    }

    // 5. Add the 'click' event listener to `topicListContainer`.
    if (topicListContainer) {
      topicListContainer.addEventListener('click', handleTopicListClick);
    }
  } catch (error) {
    console.error('Error loading topics:', error);
    topicListContainer.innerHTML = '<p class="error-message">Failed to load initial topics data.</p>';
  }
}

// --- Initial Page Load ---
loadAndInitialize();
