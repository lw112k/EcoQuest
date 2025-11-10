// assets/js/main.js
// NEW Chatbot-style FAQ JavaScript

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Mobile Menu Toggle ---
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-links');
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('is-open');
            navToggle.classList.toggle('is-active');
        });
    }

    // --- 2. FAQ Popup Logic ---
    const faqToggleButton = document.getElementById('faq-toggle-button');
    const faqCloseButton = document.getElementById('faq-close-btn');
    const faqPopup = document.getElementById('faq-popup');
    const faqListContainer = document.getElementById('faq-list');
    
    let allFaqQuestions = []; // To store the fetched questions
    let isFaqLoaded = false;

    // Helper: Displays a message in the chat window
    const appendMessage = (text, sender) => {
        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${sender}`; // 'user' or 'bot'
        messageElement.textContent = text;
        faqListContainer.appendChild(messageElement);
        // Scroll to bottom
        faqListContainer.scrollTop = faqListContainer.scrollHeight;
    };

    // Helper: Displays the list of question buttons
    const displayQuestions = () => {
        faqListContainer.innerHTML = ''; // Clear the chat
        allFaqQuestions.forEach(item => {
            const questionButton = document.createElement('button');
            questionButton.className = 'faq-question-btn';
            questionButton.textContent = item.question;
            // Set up the click event for this question
            questionButton.onclick = () => {
                displayAnswer(item.question, item.answer);
            };
            faqListContainer.appendChild(questionButton);
        });
    };

    // Helper: Displays the Q&A in a chat format
    const displayAnswer = (question, answer) => {
        faqListContainer.innerHTML = ''; // Clear questions

        // 1. Add "Back" button
        const backButton = document.createElement('button');
        backButton.className = 'faq-back-btn';
        backButton.innerHTML = '&laquo; Back to questions';
        backButton.onclick = displayQuestions; // Go back to question list
        faqListContainer.appendChild(backButton);

        // 2. Show user's question (as if they sent it)
        appendMessage(question, 'user');

        // 3. Show bot's answer after a 1-second delay
        setTimeout(() => {
            appendMessage(answer, 'bot');
        }, 1000); // 1-second delay
    };

    // Main function to fetch and initialize the FAQ data
    const loadFaq = async () => {
        // If already loaded, just show the questions list again
        if (isFaqLoaded) {
            displayQuestions();
            return;
        }

        try {
            faqListContainer.innerHTML = '<p>Loading questions...</p>';
            // Fetch from the JSON file
            const response = await fetch('../assets/data/faq.json'); 
            if (!response.ok) throw new Error('faq.json not found');
            
            const data = await response.json();
            
            if (data.questions && data.questions.length > 0) {
                allFaqQuestions = data.questions; // Store questions
                displayQuestions(); // Show the list of questions
                isFaqLoaded = true;
            } else {
                faqListContainer.innerHTML = '<p>No FAQs found.</p>';
            }
        } catch (error) {
            console.error('Error loading FAQ:', error);
            faqListContainer.innerHTML = '<p>Error loading questions.</p>';
        }
    };

    // Function to toggle the popup
    const togglePopup = () => {
        faqPopup.classList.toggle('show');
        // Load/Reload FAQs every time it's opened
        if (faqPopup.classList.contains('show')) {
            loadFaq();
        }
    };

    // Event Listeners
    if (faqToggleButton && faqPopup && faqCloseButton) {
        faqToggleButton.addEventListener('click', togglePopup);
        faqCloseButton.addEventListener('click', togglePopup);
    }
});