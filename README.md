# 🏘️ Autonomous Real Estate Sales Agent (Project LandLord)

An intelligent, AI-powered real estate web application designed to automate customer service, property matching, and lead generation. 

Instead of relying on passive contact forms, this system deploys an autonomous AI agent (powered by Google Gemini) that converses with clients, searches a live MySQL database of properties, and aggressively qualifies leads before alerting human agents in real-time via Telegram.

## 🎯 The Business Problem Solved
Traditional real estate websites suffer from high bounce rates and "anonymous traffic." Clients browse properties but rarely fill out static contact forms. Furthermore, human agents miss out on potential sales because they cannot answer inquiries 24/7 or are busy in the field.

**The Solution:** This system acts as a 24/7 digital front office. It engages users in natural language (including Swahili and Sheng), answers specific property questions based on live inventory, and uses strategic "gatekeeping" to extract contact information in exchange for high-value documents (Site Maps, Title Deeds).

## ✨ Key Features

* **🧠 Context-Aware Database Retrieval (RAG):** The AI seamlessly queries the live MySQL database to recommend properties based on user constraints (budget, location, amenities).
* **🎣 Smart Lead Extraction (The "Closer" Protocol):** The system uses advanced prompt engineering to act as a sales closer. It is programmed to withhold premium assets (like exact coordinates or PDFs) until the user provides a valid WhatsApp number.
* **📡 Real-Time Telemetry & Alerts:** Integrated with the Telegram Bot API. The moment a client inputs a valid Kenyan phone number (detected via custom Regex), the system instantly pings the human agent's phone with the lead's contact info and chat context.
* **🌍 Multilingual NLP:** Automatically detects, translates, and responds in English, Swahili, or local Kenyan Sheng, ensuring zero communication barriers.
* **🛡️ Fallback & Resilience Engine:** Built-in PHP stream fallbacks and IPv4 enforcement to ensure stable API connections even on restrictive server environments or local testing (XAMPP).

## 🛠️ Tech Stack

* **Backend:** PHP 8+, MySQL
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (Asynchronous chat interface)
* **AI Integration:** Google Gemini API (1.5 Flash / 2.0 Flash)
* **Notifications:** Telegram Bot API
* **Architecture:** RESTful API proxy handling payload construction, data sanitization, and regex-based intent trapping.

## ⚙️ System Architecture Flow

1. **User Input:** Client asks a question on the frontend interface.
2. **Proxy Intercept:** `chat_proxy.php` receives the payload and runs a Regex scan for contact details.
3. **Intent Trap:** 
   * *If a phone number is detected:* The system bypasses the AI, logs the lead in the DB, fires a Telegram alert to the broker, and returns a success message to the client.
   * *If no number is detected:* The query passes to the AI.
4. **Data Enrichment:** The PHP backend fetches the latest active inventory from MySQL and appends it to the AI's system prompt as live context.
5. **AI Response:** Gemini analyzes the prompt, matches the user to a property, and returns a formatted, persuasive response.

## 🚀 Setup & Installation

1. Clone the repository:
   ```bash
   git clone [https://github.com/CHAPLINE055/samtech-land-agency-legacy.git](https://github.com/CHAPLINE055/samtech-land-agency-legacy.git)
