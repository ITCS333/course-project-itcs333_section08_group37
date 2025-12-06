/*
  Requirement: Make the "Discussion Board" page interactive.

  Instructions:
  1. Link this file to `board.html` (or `baord.html`) using:
     <script src="board.js" defer></script>
  
  2. In `board.html`, add an `id="topic-list-container"` to the 'div'
     that holds the list of topic articles.
  
  3. The interactive features below populate and manage the topic list.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
const newTopicForm = document.querySelector('#new-topic-form');
const topicListContainer = document.querySelector('#topic-list-container');
const topicSubjectInput = document.querySelector('#topic-subject');
const topicMessageInput = document.querySelector('#topic-message');

// --- Functions ---

/**
 * Build a topic article element using the expected structure.
 * The main link's `href` is `topic.html?id=${id}` and the footer shows author/date.
 * The actions div contains an "Edit" button and a "Delete" button with class "delete-btn".
 */
function createTopicArticle(topic) {
  const { id, subject, author, date } = topic;
  const article = document.createElement('article');

  const heading = document.createElement('h3');
  const link = document.createElement('a');
  link.href = `topic.html?id=${id}`;
  link.textContent = subject;
  heading.appendChild(link);

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${author} on ${date}`;

  const actions = document.createElement('div');
  actions.className = 'actions';

  const editButton = document.createElement('button');
  editButton.type = 'button';
  editButton.textContent = 'Edit';

  const deleteButton = document.createElement('button');
  deleteButton.type = 'button';
  deleteButton.className = 'delete-btn';
  deleteButton.dataset.id = id;
  deleteButton.textContent = 'Delete';

  actions.append(editButton, deleteButton);
  article.append(heading, footer, actions);

  return article;
}

/**
 * Render all topics into the list container.
 * Clears existing content before appending freshly built article elements.
 */
function renderTopics() {
  if (!topicListContainer) return;
  topicListContainer.innerHTML = '';

  if (!topics.length) {
    const empty = document.createElement('p');
    empty.textContent = 'No topics yet. Start the conversation above.';
    topicListContainer.appendChild(empty);
    return;
  }

  topics.forEach((topic) => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

/**
 * Submit handler for creating a new topic.
 * Adds the new topic to the in-memory array, re-renders, and resets the form.
 */
function handleCreateTopic(event) {
  event.preventDefault();
  if (!topicSubjectInput || !topicMessageInput) return;

  const subject = topicSubjectInput.value.trim();
  const message = topicMessageInput.value.trim();

  if (!subject || !message) return;

  const newTopic = {
    id: `topic_${Date.now()}`,
    subject,
    message,
    author: 'Student',
    date: new Date().toISOString().split('T')[0],
  };

  topics = [newTopic, ...topics];
  renderTopics();
  newTopicForm?.reset();
}

/**
 * Delegated click handler for the topic list.
 * Removes topics when a delete button is clicked.
 */
function handleTopicListClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  if (target.classList.contains('delete-btn')) {
    const topicId = target.dataset.id;
    if (!topicId) return;

    topics = topics.filter((topic) => topic.id !== topicId);
    renderTopics();
  }
}

/**
 * Load initial data and wire up event listeners.
 */
async function loadAndInitialize() {
  try {
    const response = await fetch('topics.json');
    if (!response.ok) {
      throw new Error(`Failed to load topics: ${response.status}`);
    }
    topics = await response.json();
  } catch (error) {
    console.error('Error loading topics, using fallback data instead.', error);
    topics = [
      {
        id: 'topic_101',
        subject: 'Question about CSS Flexbox',
        message: 'I am trying to align items in a navigation bar using flexbox but cannot get them spaced evenly. Any tips?',
        author: 'Alex',
        date: '2024-01-15',
      },
      {
        id: 'topic_102',
        subject: 'Best resources to learn JavaScript',
        message: 'Looking for current tutorials and courses that balance theory with practice.',
        author: 'Taylor',
        date: '2024-02-02',
      },
      {
        id: 'topic_103',
        subject: 'Debugging tips for PHP beginners',
        message: 'New to PHP and curious how others structure error handling and logging.',
        author: 'Jordan',
        date: '2024-03-10',
      },
    ];
  }

  renderTopics();

  if (newTopicForm) {
    newTopicForm.addEventListener('submit', handleCreateTopic);
  }

  if (topicListContainer) {
    topicListContainer.addEventListener('click', handleTopicListClick);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
