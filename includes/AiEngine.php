<?php
// File: includes/AiEngine.php

class AiEngine {
    private $db;
    private $apiKey;
    private $provider;
    private $systemPrompt; 

    public function __construct($db) {
        $this->db = $db;
        // Fetch Active Settings
        $stmt = $this->db->query("SELECT * FROM ai_settings WHERE is_active=1 LIMIT 1");
        $settings = $stmt->fetch();
        if ($settings) {
            $this->apiKey = $settings['api_key'];
            $this->provider = $settings['provider'];
            // Training Instruction
            $this->systemPrompt = !empty($settings['system_prompt']) ? $settings['system_prompt'] : "You are an expert SEO Content Writer for LikexFollow.";
        }
    }

    public function generateContent($userTopic) {
        if (!$this->apiKey) return "Error: No Active API Key Found. Please add one in AI Manager.";

        // ==========================================================
        // 1. GOOGLE GEMINI (With Auto-Model Switching)
        // ==========================================================
        if ($this->provider == 'gemini') {
            
            $modelsToTry = [
                'gemini-2.0-flash',          // New Fast Model
                'gemini-1.5-flash',          // Standard Fast
                'gemini-1.5-flash-latest',   // Fallback Alias
                'gemini-1.5-pro',            // High Quality
                'gemini-pro'                 // Old Reliable
            ];

            $finalPrompt = $this->systemPrompt . "\n\nTask: Write a detailed SEO article about: " . $userTopic . "\n\nFormatting: Use HTML tags (<h2>, <h3>, <p>, <ul>, <li>). NO markdown.";
            
            $data = [
                "contents" => [
                    ["parts" => [["text" => $finalPrompt]]]
                ]
            ];

            $lastError = "";

            foreach ($modelsToTry as $model) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=" . $this->apiKey;
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $response = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $lastError = "Curl Error: " . curl_error($ch);
                    curl_close($ch);
                    continue; 
                }
                curl_close($ch);
                
                $json = json_decode($response, true);
                
                // Success
                if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                    return $json['candidates'][0]['content']['parts'][0]['text'];
                }
                
                // Track Error
                if (isset($json['error']['message'])) {
                    $lastError = $json['error']['message'];
                    // If model not found, loop continues to next model
                    // If key invalid, loop breaks
                    if (strpos($lastError, 'API key not valid') !== false) {
                        return "AI Error: Invalid API Key.";
                    }
                }
            }
            
            return "Gemini Failed: " . $lastError;
        }
        
        // ==========================================================
        // 2. OPENAI (CHATGPT)
        // ==========================================================
        if ($this->provider == 'openai') {
            $url = "https://api.openai.com/v1/chat/completions";
            $data = [
                "model" => "gpt-3.5-turbo",
                "messages" => [
                    ["role" => "system", "content" => $this->systemPrompt . " Return HTML only."],
                    ["role" => "user", "content" => "Write article: " . $userTopic]
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);
            
            $json = json_decode($response, true);
            return $json['choices'][0]['message']['content'] ?? "OpenAI Error: " . ($json['error']['message'] ?? 'Unknown Error');
        }

        // ==========================================================
        // 3. GROQ (UPDATED: AUTO-SWITCHING NEW MODELS)
        // ==========================================================
        if ($this->provider == 'groq') {
            
            // Updated List (2025/2026 Compatible)
            // Agar purana model dead hai to ye naye try karega
            $groqModels = [
                'llama-3.3-70b-versatile',  // Best & Newest
                'llama-3.1-8b-instant',     // Fastest
                'llama-3.1-70b-versatile',  // High Quality
                'mixtral-8x7b-32768',       // Fallback
                'gemma2-9b-it'              // Google's Model on Groq
            ];

            $lastGroqError = "";

            foreach ($groqModels as $model) {
                $url = "https://api.groq.com/openai/v1/chat/completions";
                
                $data = [
                    "model" => $model,
                    "messages" => [
                        ["role" => "system", "content" => $this->systemPrompt . " IMPORTANT: Output valid HTML tags (h2, p, ul) only."],
                        ["role" => "user", "content" => "Write an article about: " . $userTopic]
                    ]
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);
                curl_close($ch);
                
                $json = json_decode($response, true);

                // Check Success
                if (isset($json['choices'][0]['message']['content'])) {
                    return $json['choices'][0]['message']['content'];
                }

                // Check Error
                if (isset($json['error']['message'])) {
                    $lastGroqError = $json['error']['message'];
                    
                    // Agar error "model decommissioned" ya "not found" hai, to continue karo
                    if (strpos($lastGroqError, 'model') !== false || strpos($lastGroqError, 'not found') !== false || strpos($lastGroqError, 'decommissioned') !== false) {
                        continue; // Try next model in list
                    }
                    
                    // Agar API Key galat hai, to ruk jao
                    break;
                }
            }

            return "Groq Error (All Models Failed): " . $lastGroqError;
        }
        
        return "Provider not supported. Check AI Manager.";
    }
}
?>