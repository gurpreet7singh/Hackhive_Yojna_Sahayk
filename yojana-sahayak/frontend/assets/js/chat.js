
/* =========================================
   YOJANA SAHAYAK — CHAT JAVASCRIPT
   ========================================= */


/* ---------- API CONFIGURATION ---------- */

const API_URL = "http://127.0.0.1:8000/chat";

const PHP_CHAT_API =
    "http://localhost/yojana-sahayak/backend/api/chat.php";

const AUTH_API =
    "http://localhost/yojana-sahayak/backend/api/auth.php";


/* ---------- DOM ELEMENTS ---------- */

const chatHistoryList = document.getElementById("chatHistoryList");
const chatHistoryEmpty = document.getElementById("chatHistoryEmpty");

const messageInput = document.getElementById("messageInput");
const sendBtn = document.getElementById("sendBtn");

const chatContent = document.getElementById("chatContent");
const messagesContainer = document.getElementById("messagesContainer");

const welcomeScreen = document.getElementById("welcomeScreen");
const typingIndicator = document.getElementById("typingIndicator");

const languageSelect = document.getElementById("languageSelect");

const newChatBtn = document.getElementById("newChatBtn");

const menuToggle = document.getElementById("menuToggle");
const sidebarClose = document.getElementById("sidebarClose");
const sidebar = document.getElementById("sidebar");


/* ---------- CHAT STATE ---------- */

let isLoading = false;
let currentSessionId = null;
let isLoggedIn = false;


/* =========================================
   CHECK AUTHENTICATION STATUS
   ========================================= */

async function checkLoginStatus() {

    try {

        const response = await fetch(
            `${AUTH_API}?action=me`,
            {
                method: "GET",
                credentials: "include"
            }
        );

        const result = await response.json();

        if (
            response.ok &&
            result.success &&
            result.data?.user
        ) {

            isLoggedIn = true;

            console.log(
                "Logged-in user:",
                result.data.user.full_name
            );

            if (chatHistoryEmpty) {
                chatHistoryEmpty.textContent =
                    "Loading your conversations...";
            }

            await loadChatSessions();

        } else {

            isLoggedIn = false;
            currentSessionId = null;

            renderChatSessions([]);

        }

    } catch (error) {

        console.error("Authentication check failed:", error);

        isLoggedIn = false;
        currentSessionId = null;

        renderChatSessions([]);

    }

}


/* =========================================
   CREATE CHAT SESSION
   ========================================= */

async function createChatSession() {

    if (!isLoggedIn) {
        return null;
    }

    try {

        const selectedLanguage =
            languageSelect.value === "Hindi"
                ? "hi"
                : "en";

        const response = await fetch(
            PHP_CHAT_API,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "include",
                body: JSON.stringify({
                    action: "create_session",
                    language: selectedLanguage
                })
            }
        );

        const result = await response.json();

        if (response.ok && result.success) {

            currentSessionId = result.data;

            console.log(
                "Chat session created:",
                currentSessionId
            );

            await loadChatSessions();

            return currentSessionId;

        }

        console.error(
            "Session creation failed:",
            result.message
        );

        return null;

    } catch (error) {

        console.error("PHP Session API Error:", error);

        return null;

    }

}


/* =========================================
   SAVE CHAT MESSAGE
   ========================================= */

async function saveChatMessage(sender, message) {

    if (!isLoggedIn || !currentSessionId) {
        return;
    }

    try {

        const response = await fetch(
            PHP_CHAT_API,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "include",
                body: JSON.stringify({
                    action: "save_message",
                    session_id: currentSessionId,
                    sender: sender,
                    message: message
                })
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {

            console.error(
                "Message saving failed:",
                result.message
            );

        }

    } catch (error) {

        console.error("Message saving error:", error);

    }

}


/* =========================================
   UPDATE CONVERSATION TITLE
   ========================================= */

async function updateConversationTitle(sessionId, newTitle) {

    try {

        const response = await fetch(
            PHP_CHAT_API,
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "include",
                body: JSON.stringify({
                    session_id: sessionId,
                    title: newTitle
                })
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {

            throw new Error(
                result.message || "Unable to rename conversation."
            );

        }

        await loadChatSessions();

        return true;

    } catch (error) {

        console.error("Rename conversation error:", error);

        alert("Unable to rename conversation. Please try again.");

        return false;

    }

}


/* =========================================
   DELETE CONVERSATION
   ========================================= */

async function deleteConversation(sessionId) {

    const confirmed = confirm(
        "Are you sure you want to delete this conversation?\n\nThis action cannot be undone."
    );

    if (!confirmed) {
        return;
    }

    try {

        const response = await fetch(
            PHP_CHAT_API,
            {
                method: "DELETE",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "include",
                body: JSON.stringify({
                    session_id: sessionId
                })
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {

            throw new Error(
                result.message || "Unable to delete conversation."
            );

        }

        // If the deleted chat is currently open,
        // reset the chat screen.
        if (
            Number(currentSessionId) === Number(sessionId)
        ) {

            currentSessionId = null;

            messagesContainer.replaceChildren();

            welcomeScreen.classList.remove("d-none");

            messageInput.value = "";

            autoResizeTextarea();

            updateSendButton();

        }

        // Refresh the sidebar immediately.
        await loadChatSessions();

    } catch (error) {

        console.error("Delete conversation error:", error);

        alert("Unable to delete conversation. Please try again.");

    }

}


/* =========================================
   LOAD CHAT HISTORY
   ========================================= */

async function loadChatSessions() {

    if (!isLoggedIn || !chatHistoryList) {
        return;
    }

    try {

        const response = await fetch(
            `${PHP_CHAT_API}?action=sessions`,
            {
                method: "GET",
                credentials: "include"
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {

            throw new Error(
                result.message || "Unable to load chat sessions."
            );

        }

        const sessions =
            Array.isArray(result.data)
                ? result.data
                : [];

        renderChatSessions(sessions);

    } catch (error) {

        console.error("Chat history loading error:", error);

        if (chatHistoryEmpty && chatHistoryList) {

            chatHistoryEmpty.textContent =
                "Unable to load chat history.";

            chatHistoryList.replaceChildren(
                chatHistoryEmpty
            );

        }

    }

}


/* =========================================
   RENDER CHAT HISTORY
   WITH RENAME AND DELETE MENU
   ========================================= */

function renderChatSessions(sessions) {

    if (!chatHistoryList) {
        return;
    }

    chatHistoryList.replaceChildren();

    if (!Array.isArray(sessions) || sessions.length === 0) {

        if (chatHistoryEmpty) {

            chatHistoryEmpty.textContent =
                isLoggedIn
                    ? "No previous conversations yet."
                    : "Sign in to save your conversations.";

            chatHistoryList.appendChild(
                chatHistoryEmpty
            );

        }

        return;
    }

    sessions.forEach(session => {

        /* ---------- CHAT ITEM WRAPPER ---------- */

        const chatItem = document.createElement("div");

        chatItem.className = "chat-history-item";
        chatItem.dataset.sessionId = session.id;


        /* ---------- CONVERSATION TITLE BUTTON ---------- */

        const titleButton = document.createElement("button");

        titleButton.type = "button";

        titleButton.className = "chat-history-title";

        const title =
            session.session_title || "New Conversation";

        titleButton.textContent = title;
        titleButton.title = title;


        /* ---------- CLICK TO OPEN CHAT ---------- */

        titleButton.addEventListener("click", async () => {

            sidebar.classList.remove("open");

            await loadChatMessages(
                Number(session.id)
            );

        });


        /* ---------- THREE-DOT MENU BUTTON ---------- */

        const menuButton = document.createElement("button");

        menuButton.type = "button";

        menuButton.className = "chat-history-menu-btn";

        menuButton.innerHTML =
            '<i class="fa-solid fa-ellipsis"></i>';

        menuButton.title = "Conversation options";

        menuButton.setAttribute(
            "aria-label",
            "Conversation options"
        );


        /* ---------- DROPDOWN MENU ---------- */

        const dropdown = document.createElement("div");

        dropdown.className = "chat-history-dropdown";


        /* ---------- RENAME OPTION ---------- */

        const renameButton = document.createElement("button");

        renameButton.type = "button";

        renameButton.className = "chat-dropdown-option";

        renameButton.innerHTML =
            '<i class="fa-solid fa-pen"></i> Rename';


        renameButton.addEventListener("click", async (event) => {

            event.stopPropagation();

            dropdown.classList.remove("show");

            const newTitle = prompt(
                "Enter a new conversation name:",
                session.session_title || "New Conversation"
            );

            if (newTitle === null) {
                return;
            }

            const trimmedTitle = newTitle.trim();

            if (!trimmedTitle) {

                alert("Conversation title cannot be empty.");

                return;

            }

            await updateConversationTitle(
                Number(session.id),
                trimmedTitle
            );

        });


        /* ---------- DELETE OPTION ---------- */

        const deleteButton = document.createElement("button");

        deleteButton.type = "button";

        deleteButton.className =
            "chat-dropdown-option delete-option";

        deleteButton.innerHTML =
            '<i class="fa-solid fa-trash"></i> Delete';


        deleteButton.addEventListener("click", (event) => {

            event.stopPropagation();

            dropdown.classList.remove("show");

            deleteConversation(
                Number(session.id)
            );

        });


        /* ---------- APPEND MENU OPTIONS ---------- */

        dropdown.appendChild(renameButton);

        dropdown.appendChild(deleteButton);


        /* ---------- TOGGLE DROPDOWN ---------- */

        menuButton.addEventListener("click", (event) => {

            event.stopPropagation();

            // Close all other open menus first.
            document.querySelectorAll(
                ".chat-history-dropdown.show"
            ).forEach(openMenu => {

                if (openMenu !== dropdown) {
                    openMenu.classList.remove("show");
                }

            });

            dropdown.classList.toggle("show");

        });


        /* ---------- APPEND CHAT ITEM ---------- */

        chatItem.appendChild(titleButton);

        chatItem.appendChild(menuButton);

        chatItem.appendChild(dropdown);

        chatHistoryList.appendChild(chatItem);

    });

}


/* =========================================
   CLOSE DROPDOWN WHEN CLICKING OUTSIDE
   ========================================= */

document.addEventListener("click", () => {

    document.querySelectorAll(
        ".chat-history-dropdown.show"
    ).forEach(dropdown => {

        dropdown.classList.remove("show");

    });

});


/* =========================================
   LOAD PREVIOUS CHAT MESSAGES
   ========================================= */

async function loadChatMessages(sessionId) {

    if (
        !isLoggedIn ||
        !Number.isInteger(sessionId) ||
        sessionId <= 0
    ) {
        return;
    }

    if (isLoading) {
        return;
    }

    try {

        const response = await fetch(
            `${PHP_CHAT_API}?action=messages&session_id=${encodeURIComponent(sessionId)}`,
            {
                method: "GET",
                credentials: "include"
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {

            throw new Error(
                result.message || "Unable to load conversation."
            );

        }

        const messages =
            Array.isArray(result.data)
                ? result.data
                : [];

        messagesContainer.replaceChildren();

        welcomeScreen.classList.add("d-none");

        currentSessionId = sessionId;

        messages.forEach(message => {

            const sender =
                message.sender === "user"
                    ? "user"
                    : "assistant";

            const content =
                message.message_text ||
                message.message ||
                "";

            if (sender === "user") {

                displayUserMessage(content);

            } else {

                displayAssistantMessage(content);

            }

        });

        messageInput.value = "";

        autoResizeTextarea();

        updateSendButton();

        scrollToBottom();

    } catch (error) {

        console.error("Conversation loading error:", error);

        alert(
            "Unable to load this conversation. Please try again."
        );

    }

}


/* =========================================
   AUTO RESIZE TEXTAREA
   ========================================= */

function autoResizeTextarea() {

    messageInput.style.height = "auto";

    messageInput.style.height =
        Math.min(messageInput.scrollHeight, 160) + "px";

}


/* =========================================
   ENABLE / DISABLE SEND BUTTON
   ========================================= */

function updateSendButton() {

    const message = messageInput.value.trim();

    sendBtn.disabled =
        message.length === 0 || isLoading;

}


/* =========================================
   SCROLL TO BOTTOM
   ========================================= */

function scrollToBottom() {

    chatContent.scrollTop =
        chatContent.scrollHeight;

}


/* =========================================
   CREATE MESSAGE ELEMENT
   ========================================= */

function createMessageElement(role, text) {

    const messageDiv = document.createElement("div");

    messageDiv.className = `message ${role}`;


    /* ---------- AVATAR ---------- */

    const avatar = document.createElement("div");

    avatar.className = "message-avatar";

    if (role === "user") {

        avatar.innerHTML =
            '<i class="fa-solid fa-user"></i>';

    } else {

        avatar.innerHTML =
            '<i class="fa-solid fa-leaf"></i>';

    }


    /* ---------- MESSAGE BODY ---------- */

    const messageBody = document.createElement("div");

    messageBody.className = "message-body";


    /* ---------- ROLE LABEL ---------- */

    const roleLabel = document.createElement("div");

    roleLabel.className = "message-role";

    roleLabel.textContent =
        role === "user"
            ? "You"
            : "Yojana Sahayak";


    /* ---------- MESSAGE TEXT ---------- */

    const messageText = document.createElement("div");

    messageText.className = "message-text";

    if (
        role === "assistant" &&
        typeof marked !== "undefined"
    ) {

        messageText.innerHTML = marked.parse(text);

    } else {

        messageText.textContent = text;

    }


    /* ---------- APPEND ELEMENTS ---------- */

    messageBody.appendChild(roleLabel);

    messageBody.appendChild(messageText);

    messageDiv.appendChild(avatar);

    messageDiv.appendChild(messageBody);

    return messageDiv;

}


/* =========================================
   DISPLAY USER MESSAGE
   ========================================= */

function displayUserMessage(message) {

    const messageElement =
        createMessageElement("user", message);

    messagesContainer.appendChild(messageElement);

    scrollToBottom();

}


/* =========================================
   DISPLAY AI RESPONSE
   ========================================= */

function displayAssistantMessage(
    answer,
    sources = []
) {

    const messageElement =
        createMessageElement("assistant", answer);


    /* ---------- SOURCE CITATIONS ---------- */

    if (sources && sources.length > 0) {

        const messageBody =
            messageElement.querySelector(".message-body");

        const sourcesContainer =
            document.createElement("div");

        sourcesContainer.className = "message-sources";


        /* ---------- SOURCES TITLE ---------- */

        const sourcesTitle =
            document.createElement("div");

        sourcesTitle.className = "sources-title";

        sourcesTitle.innerHTML =
            '<i class="fa-solid fa-book-open"></i> Sources';

        sourcesContainer.appendChild(sourcesTitle);


        /* ---------- SOURCE ITEMS ---------- */

        sources.forEach(source => {

            const sourceElement =
                document.createElement("div");

            sourceElement.className = "source-item";

            const sourceIcon =
                document.createElement("i");

            sourceIcon.className =
                "fa-solid fa-file-pdf";

            const sourceText =
                document.createElement("span");


            if (typeof source === "string") {

                sourceText.textContent = source;

            } else {

                const title =
                    source.document_title ||
                    source.title ||
                    source.source ||
                    "Official document";

                const page =
                    source.page_number ||
                    source.page ||
                    source.metadata?.page_number;

                sourceText.textContent =
                    page
                        ? `${title} — Page ${page}`
                        : title;

            }


            sourceElement.appendChild(sourceIcon);

            sourceElement.appendChild(sourceText);

            sourcesContainer.appendChild(sourceElement);

        });


        messageBody.appendChild(sourcesContainer);

    }


    /* ---------- APPEND MESSAGE ---------- */

    messagesContainer.appendChild(messageElement);

    scrollToBottom();

}


/* =========================================
   SHOW / HIDE TYPING INDICATOR
   ========================================= */

function showTypingIndicator() {

    typingIndicator.classList.remove("d-none");

    scrollToBottom();

}


function hideTypingIndicator() {

    typingIndicator.classList.add("d-none");

}


/* =========================================
   SEND MESSAGE TO FASTAPI
   ========================================= */

async function sendMessage(message = null) {

    const userMessage =
        (message || messageInput.value).trim();

    if (!userMessage || isLoading) {
        return;
    }


    /* ---------- LOADING STATE ---------- */

    isLoading = true;

    updateSendButton();


    /* ---------- DISPLAY USER MESSAGE ---------- */

    welcomeScreen.classList.add("d-none");

    displayUserMessage(userMessage);


    /* ---------- CREATE SESSION IF NECESSARY ---------- */

    if (isLoggedIn && !currentSessionId) {

        await createChatSession();

    }


    /* ---------- AUTO-GENERATE CONVERSATION TITLE ---------- */

    if (isLoggedIn && currentSessionId) {

        const currentSession =
            document.querySelector(
                `.chat-history-item[data-session-id="${currentSessionId}"] .chat-history-title`
            );

        if (
            !currentSession ||
            currentSession.textContent === "New Conversation"
        ) {

            const autoTitle =
                userMessage.length > 45
                    ? userMessage.substring(0, 45) + "..."
                    : userMessage;

            await updateConversationTitle(
                Number(currentSessionId),
                autoTitle
            );

        }

    }


    /* ---------- SAVE USER MESSAGE ---------- */

    if (isLoggedIn && currentSessionId) {

        await saveChatMessage(
            "user",
            userMessage
        );

    }


    /* ---------- CLEAR INPUT ---------- */

    messageInput.value = "";

    autoResizeTextarea();


    /* ---------- SHOW TYPING ---------- */

    showTypingIndicator();


    try {

        /* ---------- API REQUEST ---------- */

        const response = await fetch(
            API_URL,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    question: userMessage,
                    language: languageSelect.value
                })
            }
        );


        /* ---------- HANDLE HTTP ERRORS ---------- */

        if (!response.ok) {

            throw new Error(
                `Server error: ${response.status}`
            );

        }


        /* ---------- PARSE JSON ---------- */

        const data = await response.json();


        /* ---------- HIDE TYPING ---------- */

        hideTypingIndicator();


        /* ---------- DISPLAY AI RESPONSE ---------- */

        const assistantAnswer =
            data.answer ||
            "Sorry, I couldn't generate a response.";

        displayAssistantMessage(
            assistantAnswer,
            data.sources || []
        );


        /* ---------- SAVE AI RESPONSE ---------- */

        if (isLoggedIn && currentSessionId) {

            await saveChatMessage(
                "assistant",
                assistantAnswer
            );

        }


        /* ---------- REFRESH SIDEBAR ---------- */

        if (isLoggedIn) {

            await loadChatSessions();

        }

    } catch (error) {

        console.error("Chat API Error:", error);

        hideTypingIndicator();

        displayAssistantMessage(
            "I'm having trouble connecting to the server right now. " +
            "Please ensure the AI backend is running and try again."
        );

    } finally {

        isLoading = false;

        updateSendButton();

        messageInput.focus();

    }

}


/* =========================================
   SEND BUTTON CLICK
   ========================================= */

sendBtn.addEventListener("click", () => {

    sendMessage();

});


/* =========================================
   ENTER TO SEND
   SHIFT + ENTER = NEW LINE
   ========================================= */

messageInput.addEventListener(
    "keydown",
    (event) => {

        if (
            event.key === "Enter" &&
            !event.shiftKey
        ) {

            event.preventDefault();

            sendMessage();

        }

    }
);


/* =========================================
   TEXTAREA INPUT
   ========================================= */

messageInput.addEventListener("input", () => {

    autoResizeTextarea();

    updateSendButton();

});


/* =========================================
   SUGGESTED QUESTIONS
   ========================================= */

document.querySelectorAll(".suggestion-card").forEach(card => {

    card.addEventListener("click", () => {

        const question = card.dataset.question;

        if (question) {

            sendMessage(question);

        }

    });

});


/* =========================================
   NEW CHAT
   ========================================= */

newChatBtn.addEventListener(
    "click",
    async () => {

        /* ---------- CLEAR MESSAGES ---------- */

        messagesContainer.replaceChildren();


        /* ---------- SHOW WELCOME ---------- */

        welcomeScreen.classList.remove("d-none");


        /* ---------- RESET INPUT ---------- */

        messageInput.value = "";

        autoResizeTextarea();


        /* ---------- RESET LOADING ---------- */

        isLoading = false;

        hideTypingIndicator();

        updateSendButton();


        /* ---------- CLOSE MOBILE SIDEBAR ---------- */

        sidebar.classList.remove("open");


        /* ---------- RESET SESSION ---------- */

        currentSessionId = null;


        /* ---------- CREATE NEW SESSION ---------- */

        if (isLoggedIn) {

            await createChatSession();

        }


        /* ---------- FOCUS INPUT ---------- */

        messageInput.focus();

    }
);


/* =========================================
   MOBILE SIDEBAR
   ========================================= */

menuToggle.addEventListener("click", () => {

    sidebar.classList.add("open");

});


sidebarClose.addEventListener("click", () => {

    sidebar.classList.remove("open");

});


/* =========================================
   SIDEBAR NAVIGATION
   ========================================= */

document.querySelectorAll(".sidebar-link").forEach(link => {

    link.addEventListener("click", (event) => {

        event.preventDefault();


        /* ---------- ACTIVE STATE ---------- */

        document.querySelectorAll(".sidebar-link").forEach(item => {

            item.classList.remove("active");

        });

        link.classList.add("active");


        /* ---------- CLOSE MOBILE SIDEBAR ---------- */

        sidebar.classList.remove("open");


        const section = link.dataset.section;


        if (section !== "chat") {

            alert(
                "This feature is coming soon! " +
                "For now, you can use the AI Assistant."
            );

        }

    });

});


/* =========================================
   INITIALIZE
   ========================================= */

document.addEventListener(
    "DOMContentLoaded",
    async () => {

        updateSendButton();

        autoResizeTextarea();

        // Detect the actual PHP login session.
        await checkLoginStatus();

    }
);
