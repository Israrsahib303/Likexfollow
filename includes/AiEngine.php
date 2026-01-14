<?php
// File: includes/AiEngine.php

class AiEngine {
    private $db;
    private $apiKey;
    private $provider;
    private $systemPrompt; 

    public function __construct($db) {
        $this->db = $db;
        // Fetch Active Settings from Database
        $stmt = $this->db->query("SELECT * FROM ai_settings WHERE is_active=1 LIMIT 1");
        $settings = $stmt->fetch();
        
        if ($settings) {
            $this->apiKey = trim($settings['api_key']);
            $this->provider = strtolower(trim($settings['provider']));
            
            // Shortened System Prompt to Save Tokens
            $defaultPrompt = "You are a helpful SMM Assistant.";
            $this->systemPrompt = !empty($settings['system_prompt']) ? $settings['system_prompt'] : $defaultPrompt;
        }
    }

    public function generateContent($userTopic, $format = 'html') {
        if (!$this->apiKey) {
            return "Error: No Active API Key Found. Check Admin Panel.";
        }

        // --- PROMPT ENGINEERING (COMPRESSED) ---
        // We reduced instructions to save tokens
        if ($format == 'html') {
            $formatInstruction = "Format: Use HTML tags (<h2>, <p>). No Markdown.";
        } else {
            $formatInstruction = "Format: PLAIN TEXT ONLY. No Markdown, No HTML, No Intro.";
        }

        $structuredPrompt = "
        TASK: $userTopic
        $formatInstruction
        ";

        // ==========================================================
        // 1. GROQ (OPTIMIZED FOR FREE TIER)
        // ==========================================================
        if ($this->provider == 'groq') {
            
            // 2025 Stable Model List
            $groqModels = [
                'llama-3.3-70b-versatile',   
                'llama-3.1-8b-instant'       
            ];

            $lastGroqError = "";

            foreach ($groqModels as $model) {
                $url = "https://api.groq.com/openai/v1/chat/completions";
                
                $data = [
                    "model" => $model,
                    "messages" => [
                        [
                            "role" => "system", 
                            "content" => $this->systemPrompt
                        ],
                        [
                            "role" => "user", 
                            "content" => $structuredPrompt
                        ]
                    ],
                    "temperature" => 0.7,
                    "max_tokens" => 1000, // Reduced to prevent hitting 6000 TPM limit
                    "stream" => false
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); 

                $response = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $lastGroqError = "Curl Error: " . curl_error($ch);
                    curl_close($ch);
                    continue; 
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $json = json_decode($response, true);

                // Success
                if ($httpCode === 200 && isset($json['choices'][0]['message']['content'])) {
                    $content = $json['choices'][0]['message']['content'];
                    
                    if ($format == 'text') {
                        $content = str_replace(['```html', '```json', '```'], '', $content);
                        $content = strip_tags($content);
                        return trim($content, '"\'');
                    } else {
                        return str_replace(['```html', '```'], '', $content);
                    }
                }

                // Error Tracking
                if (isset($json['error']['message'])) {
                    $lastGroqError = $json['error']['message'];
                    // If Rate Limit (TPM), try next model or stop
                    if (strpos($lastGroqError, 'TPM') !== false) {
                        return "Groq Error: Rate Limit Reached (Wait 1 min).";
                    }
                    if (strpos($lastGroqError, 'authentication') !== false) {
                        return "Groq Error: Invalid API Key.";
                    }
                }
            }

            return "Groq Failed: " . $lastGroqError;
        }

        // ==========================================================
        // 2. OPENAI (BACKUP)
        // ==========================================================
        if ($this->provider == 'openai') {
            $url = "[https://api.openai.com/v1/chat/completions](https://api.openai.com/v1/chat/completions)";
            $data = [
                "model" => "gpt-3.5-turbo",
                "messages" => [
                    ["role" => "system", "content" => $this->systemPrompt],
                    ["role" => "user", "content" => $structuredPrompt]
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
            
            if (isset($json['choices'][0]['message']['content'])) {
                return $json['choices'][0]['message']['content'];
            }
            return "OpenAI Error.";
        }

        return "Error: Provider not configured.";
    }
}
?>