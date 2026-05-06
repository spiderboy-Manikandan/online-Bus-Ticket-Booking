<?php
session_start();
include "db.php";

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['name'] : 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Chatbot - Great Bus Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-gradient: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            --card-bg: #ffffff;
            --text-primary: #333;
            --text-secondary: #666;
            --navbar-bg: rgba(255, 255, 255, 0.95);
            --chat-user-bg: linear-gradient(135deg, #FF6B35, #F7931E);
            --chat-bot-bg: #f0f2f5;
            --input-border: #e0e0e0;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --navbar-bg: rgba(30, 30, 46, 0.95);
            --chat-bot-bg: #2a2a3a;
            --input-border: #3a3a4a;
            --shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            transition: all 0.3s ease;
            position: relative;
        }

        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .floating-bus {
            position: absolute;
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.08);
            animation: floatBus 20s infinite linear;
        }

        @keyframes floatBus {
            0% { transform: translateX(-10%) translateY(10vh); opacity: 0; }
            10% { opacity: 0.1; }
            90% { opacity: 0.1; }
            100% { transform: translateX(110vw) translateY(-10vh); opacity: 0; }
        }

        .floating-bus:nth-child(1) { top: 20%; left: -5%; animation-duration: 18s; }
        .floating-bus:nth-child(2) { top: 50%; left: -10%; animation-duration: 25s; animation-delay: 3s; }
        .floating-bus:nth-child(3) { top: 70%; left: -3%; animation-duration: 22s; animation-delay: 6s; }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50px;
            padding: 8px 18px;
            color: white;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.05);
            background: rgba(0, 0, 0, 0.8);
        }

        .navbar {
            position: relative;
            z-index: 10;
            background: var(--navbar-bg);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .logo i {
            color: #FF6B35;
            margin-right: 10px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: var(--text-primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 107, 53, 0.1);
        }

        .chat-container {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 1rem auto;
            padding: 0 2rem;
            height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: var(--card-bg);
            border-radius: 30px 30px 0 0;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow);
        }

        .chat-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .chat-avatar i {
            font-size: 1.8rem;
            color: white;
        }

        .chat-info h3 {
            color: var(--text-primary);
            font-size: 1.2rem;
        }

        .chat-info p {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .online-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            margin-right: 5px;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .chat-messages {
            flex: 1;
            background: var(--card-bg);
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            gap: 0.8rem;
            animation: messageSlide 0.3s ease;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message.bot {
            justify-content: flex-start;
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message.bot .message-avatar {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
        }

        .message.user .message-avatar {
            background: #10b981;
            order: 2;
        }

        .message-avatar i {
            color: white;
            font-size: 1rem;
        }

        .message-content {
            max-width: 70%;
            padding: 0.8rem 1.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .message.bot .message-content {
            background: var(--chat-bot-bg);
            color: var(--text-primary);
            border-radius: 20px 20px 20px 5px;
        }

        .message.user .message-content {
            background: var(--chat-user-bg);
            color: white;
            border-radius: 20px 20px 5px 20px;
        }

        .message-time {
            font-size: 0.65rem;
            color: var(--text-secondary);
            margin-top: 0.3rem;
            display: block;
        }

        .quick-replies {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .quick-reply-btn {
            background: rgba(255, 107, 53, 0.1);
            border: 1px solid #FF6B35;
            color: #FF6B35;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-reply-btn:hover {
            background: #FF6B35;
            color: white;
            transform: translateY(-2px);
        }

        .typing-indicator {
            display: flex;
            gap: 0.3rem;
            padding: 0.5rem 1rem;
            background: var(--chat-bot-bg);
            border-radius: 20px;
            width: fit-content;
        }

        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #FF6B35;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.4;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }

        .chat-input-area {
            background: var(--card-bg);
            border-radius: 0 0 30px 30px;
            padding: 1rem 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .chat-input {
            flex: 1;
            display: flex;
            gap: 0.5rem;
        }

        .chat-input input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 60px;
            font-size: 0.9rem;
            background: var(--card-bg);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .chat-input input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .send-btn, .clear-btn {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover, .clear-btn:hover {
            transform: scale(1.05);
        }

        .clear-btn {
            background: #6c757d;
        }

        .mic-btn {
            background: rgba(255, 107, 53, 0.1);
            color: #FF6B35;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mic-btn:hover {
            background: #FF6B35;
            color: white;
        }

        .chat-messages::-webkit-scrollbar {
            width: 5px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: var(--input-border);
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #FF6B35;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .chat-container { padding: 0 1rem; height: calc(100vh - 70px); }
            .message-content { max-width: 85%; }
            .navbar { flex-direction: column; text-align: center; }
            .chat-header { padding: 0.8rem 1rem; }
            .chat-avatar { width: 40px; height: 40px; }
            .chat-avatar i { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

<div class="animated-bg">
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-bus-simple"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
</div>

<button class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i> <span>Dark Mode</span>
</button>

<nav class="navbar">
    <div class="logo">
        <i class="fas fa-bus"></i> Great Bus
    </div>
    <div class="nav-links">
        <a href="search_bus.php"><i class="fas fa-search"></i> Search Buses</a>
        <?php if ($is_logged_in): ?>
            <a href="user_account.php"><i class="fas fa-user-circle"></i> My Account</a>
            <a href="logout.php" style="background: #ff4757; color: white;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="login.php?type=user"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<div class="chat-container">
    <div class="chat-header">
        <div class="chat-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="chat-info">
            <h3>Great Bus Assistant <span class="online-status"></span> Online</h3>
            <p>Ask me anything about bus bookings, routes, payments, and more!</p>
        </div>
    </div>

    <div class="chat-messages" id="chatMessages">
        <div class="message bot">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <div>👋 Hello <?= htmlspecialchars($user_name) ?>! I'm your Great Bus Assistant.</div>
                <div style="margin-top: 8px;">I can help you with:</div>
                <div class="quick-replies">
                    <button class="quick-reply-btn" onclick="quickReply('book bus')">🚌 Book a Bus</button>
                    <button class="quick-reply-btn" onclick="quickReply('booking status')">🎫 Check Booking</button>
                    <button class="quick-reply-btn" onclick="quickReply('bus routes')">🗺️ Routes</button>
                    <button class="quick-reply-btn" onclick="quickReply('payment')">💰 Payment Help</button>
                    <button class="quick-reply-btn" onclick="quickReply('cancel booking')">📋 Cancellation</button>
                </div>
                <span class="message-time">Just now</span>
            </div>
        </div>
    </div>

    <div class="chat-input-area">
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Type your message here..." onkeypress="handleKeyPress(event)">
            <button class="send-btn" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <button class="mic-btn" onclick="startVoiceRecognition()">
            <i class="fas fa-microphone"></i>
        </button>
        <button class="clear-btn" onclick="clearChat()" title="Clear Chat">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>
</div>

<script>
    // Bot response system
    function getBotResponse(userMessage) {
        let msg = userMessage.toLowerCase().trim();
        
        // Greetings
        if (msg.match(/^(hi|hello|hey|good morning|good evening|namaste)$/)) {
            return "Hello! 👋 Welcome to Great Bus. How can I help you today? You can ask me about booking buses, checking routes, payments, or any other travel questions!";
        }
        
        // Book a bus
        if (msg.match(/book|booking|want to book|need to book|bus booking/)) {
            return "🎫 To book a bus:\n\n✅ Go to 'Search Buses' page\n✅ Enter your source and destination cities\n✅ Select your travel date\n✅ Choose your preferred bus\n✅ Select seats\n✅ Enter passenger details\n✅ Complete payment\n\nWould you like me to take you to the search page?";
        }
        
        // Booking status
        if (msg.match(/booking status|check booking|my booking|ticket status/)) {
            return "🎟️ To check your booking status:\n\n✅ Login to your account\n✅ Go to 'My Account'\n✅ Click on 'My Bookings'\n✅ View all your bookings with status\n\nNeed help logging in?";
        }
        
        // Routes
        if (msg.match(/route|routes|available routes|cities|destinations/)) {
            return "🗺️ We operate on these popular routes:\n\n🚌 Mumbai → Pune\n🚌 Delhi → Jaipur\n🚌 Bangalore → Chennai\n🚌 Hyderabad → Vijayawada\n🚌 Chennai → Coimbatore\n🚌 Kolkata → Digha\n\nWhich route are you interested in?";
        }
        
        // Payment
        if (msg.match(/payment|pay|upi|card|net banking|wallet/)) {
            return "💰 We accept multiple payment methods:\n\n✅ Credit/Debit Cards\n✅ UPI (Google Pay, PhonePe, Paytm)\n✅ Net Banking\n✅ Mobile Wallets\n\nAll payments are 100% secure with SSL encryption!";
        }
        
        // Cancellation
        if (msg.match(/cancel|cancellation|refund/)) {
            return "📋 Cancellation Policy:\n\n✅ Free cancellation - 24 hours before departure\n✅ 50% refund - 12-24 hours before\n❌ No refund - Less than 12 hours\n\nHow to cancel:\n1. Go to 'My Account'\n2. Open 'My Bookings'\n3. Click 'Cancel' on the booking\n4. Confirm cancellation";
        }
        
        // Seats
        if (msg.match(/seat|seats|select seat|choose seat/)) {
            return "🪑 Seat Selection:\n\n✅ Click on any available seat (green color)\n✅ Selected seats turn green\n✅ Booked seats are red\n✅ You can select multiple seats\n✅ Price per seat is shown on the bus card\n✅ Total price updates automatically";
        }
        
        // Prices
        if (msg.match(/price|cost|fare|ticket price/)) {
            return "💰 Bus fares depend on:\n\n✅ Distance of travel\n✅ Bus type (AC/Non-AC/Sleeper)\n✅ Travel date (peak/off-season)\n✅ Seat preference\n\nCheck the search page for exact prices!";
        }
        
        // Time/Schedule
        if (msg.match(/time|schedule|departure|arrival|when/)) {
            return "⏰ Bus schedules:\n\n✅ Departure and arrival times are shown on each bus card\n✅ Morning, afternoon, evening, and night buses available\n✅ Journey duration varies by route\n✅ Real-time tracking available for some buses\n\nCheck specific bus for timings!";
        }
        
        // Amenities
        if (msg.match(/amenities|facility|features|wifi|ac|charging/)) {
            return "✨ Our buses offer:\n\n✅ Free WiFi\n✅ USB Charging Points\n✅ AC/Non-AC options\n✅ Water Bottles\n✅ Blankets (sleeper buses)\n✅ Entertainment screens\n✅ First Aid Kit\n✅ GPS Tracking";
        }
        
        // Luggage
        if (msg.match(/luggage|baggage|bag|carry/)) {
            return "🧳 Luggage Policy:\n\n✅ 15kg check-in luggage per passenger\n✅ 7kg hand luggage\n✅ Extra luggage can be added for a fee\n✅ Sports equipment subject to availability\n\nContact support for special baggage needs!";
        }
        
        // Discount/Offer
        if (msg.match(/discount|offer|coupon|promo code/)) {
            return "🎉 Current Offers:\n\n✅ EARLY10 - 10% off (book 7 days early)\n✅ GROUP15 - 15% off (booking 5+ seats)\n✅ STUDENT5 - 5% off (valid ID required)\n✅ WEEKEND20 - 20% off on weekend trips\n\nUse code at checkout!";
        }
        
        // Account Help
        if (msg.match(/account|profile|password|forgot password/)) {
            return "👤 Account Help:\n\n✅ Login using email and password\n✅ Forgot password? Click 'Forgot Password' on login page\n✅ Update profile in 'My Account'\n✅ Change password in account settings\n✅ View all bookings in 'My Bookings'\n\nNeed help signing up?";
        }
        
        // Contact Support
        if (msg.match(/support|help|contact|customer care/)) {
            return "📞 Customer Support:\n\n✅ 24/7 Helpline: 1800-XXX-XXXX\n✅ Email: support@greatbus.com\n✅ WhatsApp: +91-XXXXXXXXXX\n✅ Live Chat: Available 9 AM - 9 PM\n\nWe're here to help you!";
        }
        
        // Thanks
        if (msg.match(/thank|thanks|thx/)) {
            return "You're very welcome! 😊 Happy to help! Have a great journey with Great Bus! 🚌✨";
        }
        
        // Bye
        if (msg.match(/bye|goodbye|see you/)) {
            return "Goodbye! 👋 Safe travels! Come back to Great Bus for your next journey. 🚌";
        }
        
        // Default response
        return "I'm here to help with bus bookings! 🚌\n\nYou can ask me about:\n🔹 Booking a bus\n🔹 Checking booking status\n🔹 Bus routes & schedules\n🔹 Prices & discounts\n🔹 Payment methods\n🔹 Cancellation policy\n🔹 Seat selection\n🔹 Account help\n\nJust type your question!";
    }

    // Add message to chat
    function addMessage(text, isUser) {
        const messagesDiv = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;
        
        const now = new Date();
        const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <i class="${isUser ? 'fas fa-user' : 'fas fa-robot'}"></i>
            </div>
            <div class="message-content">
                <div>${text.replace(/\n/g, '<br>')}</div>
                <span class="message-time">${timeString}</span>
            </div>
        `;
        
        messagesDiv.appendChild(messageDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
        
        // Save to localStorage
        saveChatToStorage();
    }
    
    // Show typing indicator
    function showTyping() {
        const messagesDiv = document.getElementById('chatMessages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message bot';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <div class="typing-indicator">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `;
        messagesDiv.appendChild(typingDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
    
    // Hide typing indicator
    function hideTyping() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.remove();
    }
    
    // Send message
    function sendMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        
        if (message === '') return;
        
        // Add user message
        addMessage(message, true);
        input.value = '';
        
        // Show typing indicator
        showTyping();
        
        // Get bot response after delay
        setTimeout(() => {
            hideTyping();
            const response = getBotResponse(message);
            addMessage(response, false);
        }, 500);
    }
    
    // Quick reply
    function quickReply(text) {
        document.getElementById('chatInput').value = text;
        sendMessage();
    }
    
    // Handle enter key
    function handleKeyPress(event) {
        if (event.key === 'Enter') {
            sendMessage();
        }
    }
    
    // Voice recognition
    function startVoiceRecognition() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.lang = 'en-US';
            recognition.interimResults = false;
            
            recognition.onstart = function() {
                const micBtn = document.querySelector('.mic-btn');
                micBtn.style.background = '#FF6B35';
                micBtn.style.color = 'white';
            };
            
            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                document.getElementById('chatInput').value = transcript;
                sendMessage();
            };
            
            recognition.onerror = function() {
                alert('Voice recognition error. Please try typing your message.');
            };
            
            recognition.onend = function() {
                const micBtn = document.querySelector('.mic-btn');
                micBtn.style.background = '';
                micBtn.style.color = '';
            };
            
            recognition.start();
        } else {
            alert('Voice recognition is not supported in your browser. Please type your message instead.');
        }
    }
    
    // Save chat to localStorage
    function saveChatToStorage() {
        const messages = [];
        const messageElements = document.querySelectorAll('#chatMessages .message');
        messageElements.forEach(msg => {
            const isUser = msg.classList.contains('user');
            const textDiv = msg.querySelector('.message-content div:first-child');
            if (textDiv && !textDiv.querySelector('.typing-indicator')) {
                const text = textDiv.innerHTML.replace(/<br>/g, '\n');
                messages.push({ isUser, text });
            }
        });
        localStorage.setItem('greatbus_chat', JSON.stringify(messages));
    }
    
    // Load chat from localStorage
    function loadChatFromStorage() {
        const saved = localStorage.getItem('greatbus_chat');
        if (saved) {
            const messages = JSON.parse(saved);
            const messagesDiv = document.getElementById('chatMessages');
            // Clear welcome message but keep it
            const welcomeMsg = messagesDiv.querySelector('.message.bot:first-child');
            messagesDiv.innerHTML = '';
            if (welcomeMsg) messagesDiv.appendChild(welcomeMsg);
            
            messages.forEach(msg => {
                const now = new Date();
                const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.isUser ? 'user' : 'bot'}`;
                messageDiv.innerHTML = `
                    <div class="message-avatar">
                        <i class="${msg.isUser ? 'fas fa-user' : 'fas fa-robot'}"></i>
                    </div>
                    <div class="message-content">
                        <div>${msg.text.replace(/\n/g, '<br>')}</div>
                        <span class="message-time">${timeString}</span>
                    </div>
                `;
                messagesDiv.appendChild(messageDiv);
            });
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
    }
    
    // Clear chat
    function clearChat() {
        if (confirm('Clear all chat history?')) {
            localStorage.removeItem('greatbus_chat');
            location.reload();
        }
    }
    
    // Dark/Light Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const icon = themeToggle.querySelector('i');
    const text = themeToggle.querySelector('span');

    const savedTheme = localStorage.getItem('greatbus-theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark');
        icon.className = 'fas fa-sun';
        text.textContent = 'Light Mode';
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark');
        if (body.classList.contains('dark')) {
            localStorage.setItem('greatbus-theme', 'dark');
            icon.className = 'fas fa-sun';
            text.textContent = 'Light Mode';
        } else {
            localStorage.setItem('greatbus-theme', 'light');
            icon.className = 'fas fa-moon';
            text.textContent = 'Dark Mode';
        }
    });
    
    // Load saved chat on page load
    loadChatFromStorage();

    console.log("%c🚌 Great Bus | AI Chatbot Assistant", "color: #FF6B35; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Voice Recognition | Smart Responses | Chat History", "color: #F7931E; font-size: 12px;");
</script>

</body>
</html>