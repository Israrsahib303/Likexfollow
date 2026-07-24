<?php
// ==========================================
// 1. BACKEND: PHP PROXY LOGIC (Runs on Server)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['is_ajax'])) {
    header('Content-Type: application/json');
    
    $apiUrl = isset($_POST['apiUrl']) ? trim($_POST['apiUrl']) : '';
    $apiKey = isset($_POST['apiKey']) ? trim($_POST['apiKey']) : '';

    if (empty($apiUrl) || empty($apiKey)) {
        echo json_encode(['error' => 'API URL aur API Key dono zaroori hain.']);
        exit;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['key' => $apiKey, 'action' => 'services']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Yeh line bohot important hai! Kuch SMM panels strict SSL rakhte hain, yeh usko bypass karegi
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['error' => 'Server cURL Error: ' . $error]);
    } else {
        echo $response;
    }
    exit; // Yahan exit karna zaroori hai taake niche ka HTML JSON me mix na ho
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pro SMM API Fetcher ⚡</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 font-sans min-h-screen p-4 md:p-8 flex justify-center items-start">

    <div class="w-full max-w-3xl bg-slate-800 rounded-2xl shadow-2xl p-6 md:p-10 border border-slate-700 mt-10">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400 mb-2">
                SMM API Explorer 🚀
            </h1>
            <p class="text-slate-400 text-sm">Enter API details to fetch categories and services instantly.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
            <div>
                <label class="block text-sm font-semibold mb-2 text-slate-300">API URL 🌐</label>
                <input type="text" id="apiUrl" placeholder="https://panel.com/api/v2" 
                    class="w-full bg-slate-900 border border-slate-600 rounded-xl p-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2 text-slate-300">API Key 🔑</label>
                <input type="password" id="apiKey" placeholder="Your Secret Key" 
                    class="w-full bg-slate-900 border border-slate-600 rounded-xl p-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
        </div>

        <button id="fetchBtn" onclick="fetchSmmData()" 
            class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-3 px-4 rounded-xl shadow-lg transform transition-all active:scale-95 mb-8">
            Fetch Services ⚡
        </button>

        <div id="dropdownSection" class="hidden space-y-6 animate-fade-in">
            <div>
                <label class="block text-sm font-semibold mb-2 text-slate-300">Select Category 📁</label>
                <select id="categorySelect" onchange="loadServices()" 
                    class="w-full bg-slate-900 border border-slate-600 rounded-xl p-3 text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all cursor-pointer">
                    <option value="">-- Choose a Category --</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold mb-2 text-slate-300">Select Service 🛒</label>
                <select id="serviceSelect" onchange="showServiceDetails()" disabled 
                    class="w-full bg-slate-900 border border-slate-600 rounded-xl p-3 text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                    <option value="">-- Waiting for Category --</option>
                </select>
            </div>
        </div>

        <div id="serviceDetails" class="hidden mt-8 bg-slate-750 border border-slate-600 rounded-xl p-6 bg-slate-900/50 shadow-inner">
            <h2 class="text-xl font-bold mb-4 text-emerald-400 flex items-center gap-2">
                <span>📋</span> Service Details
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm mb-6" id="detailsGrid"></div>
            <div class="p-4 bg-slate-800 rounded-lg border border-slate-700">
                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">Description / Notes</p>
                <p id="serviceDesc" class="text-slate-200 text-sm whitespace-pre-wrap leading-relaxed"></p>
            </div>
        </div>

    </div>

    <script>
        let globalServices = [];
        let categoriesMap = {};

        async function fetchSmmData() {
            const apiUrl = document.getElementById('apiUrl').value.trim();
            const apiKey = document.getElementById('apiKey').value.trim();
            const fetchBtn = document.getElementById('fetchBtn');

            if (!apiUrl || !apiKey) {
                alert('Bhai, API URL aur Key dono zaroori hain! 🛑');
                return;
            }

            fetchBtn.innerHTML = 'Fetching... ⏳';
            fetchBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('is_ajax', '1'); // PHP ko batane ke liye ke ye API request hai
                formData.append('apiUrl', apiUrl);
                formData.append('apiKey', apiKey);

                // Fetching exactly the same file we are on (smm.php)
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                // Check agar response theek nahi aaya (like 500 server error)
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.error) {
                    alert('SMM Panel Error: ' + data.error);
                    resetUI();
                    return;
                }

                globalServices = data;
                organizeCategories(data);
                
                document.getElementById('dropdownSection').classList.remove('hidden');
                document.getElementById('serviceDetails').classList.add('hidden');

            } catch (error) {
                console.error(error);
                // Ab actual error message show hoga, purana hardcoded wala nahi
                alert('Oops! Kuch masla ho gaya. Console check karo: ' + error.message);
            } finally {
                fetchBtn.innerHTML = 'Fetch Services ⚡';
                fetchBtn.disabled = false;
            }
        }

        function organizeCategories(services) {
            categoriesMap = {};
            const categorySelect = document.getElementById('categorySelect');
            
            services.forEach(service => {
                if (!categoriesMap[service.category]) {
                    categoriesMap[service.category] = [];
                }
                categoriesMap[service.category].push(service);
            });

            categorySelect.innerHTML = '<option value="">-- Select Category --</option>';
            Object.keys(categoriesMap).forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categorySelect.appendChild(option);
            });

            const serviceSelect = document.getElementById('serviceSelect');
            serviceSelect.innerHTML = '<option value="">-- Choose Service --</option>';
            serviceSelect.disabled = true;
        }

        function loadServices() {
            const selectedCategory = document.getElementById('categorySelect').value;
            const serviceSelect = document.getElementById('serviceSelect');
            
            document.getElementById('serviceDetails').classList.add('hidden');

            if (!selectedCategory) {
                serviceSelect.innerHTML = '<option value="">-- Waiting for Category --</option>';
                serviceSelect.disabled = true;
                return;
            }

            const services = categoriesMap[selectedCategory];
            serviceSelect.innerHTML = '<option value="">-- Select Service --</option>';
            
            services.forEach(service => {
                const option = document.createElement('option');
                option.value = service.service;
                option.textContent = `ID: ${service.service} - ${service.name}`;
                serviceSelect.appendChild(option);
            });

            serviceSelect.disabled = false;
        }

        function showServiceDetails() {
            const selectedServiceId = document.getElementById('serviceSelect').value;
            const detailsPanel = document.getElementById('serviceDetails');
            
            if (!selectedServiceId) {
                detailsPanel.classList.add('hidden');
                return;
            }

            const service = globalServices.find(s => s.service == selectedServiceId);
            if (!service) return;

            const grid = document.getElementById('detailsGrid');
            grid.innerHTML = `
                <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                    <span class="block text-slate-400 text-xs uppercase mb-1">Rate per 1000</span>
                    <span class="font-bold text-lg text-emerald-400">$${service.rate}</span>
                </div>
                <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                    <span class="block text-slate-400 text-xs uppercase mb-1">Min Order</span>
                    <span class="font-bold text-slate-200">${service.min}</span>
                </div>
                <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                    <span class="block text-slate-400 text-xs uppercase mb-1">Max Order</span>
                    <span class="font-bold text-slate-200">${service.max}</span>
                </div>
                <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                    <span class="block text-slate-400 text-xs uppercase mb-1">Refill Button</span>
                    <span class="font-bold ${service.refill ? 'text-green-400' : 'text-red-400'}">
                        ${service.refill ? '✅ Available' : '❌ No'}
                    </span>
                </div>
                <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                    <span class="block text-slate-400 text-xs uppercase mb-1">Cancel Button</span>
                    <span class="font-bold ${service.cancel ? 'text-green-400' : 'text-red-400'}">
                        ${service.cancel ? '✅ Available' : '❌ No'}
                    </span>
                </div>
                <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                    <span class="block text-slate-400 text-xs uppercase mb-1">Service Type</span>
                    <span class="font-bold text-slate-200 capitalize">${service.type || 'Default'}</span>
                </div>
            `;

            document.getElementById('serviceDesc').innerHTML = service.desc || 'No description provided by panel.';
            detailsPanel.classList.remove('hidden');
        }

        function resetUI() {
            document.getElementById('dropdownSection').classList.add('hidden');
            document.getElementById('serviceDetails').classList.add('hidden');
            globalServices = [];
            categoriesMap = {};
        }
    </script>
</body>
</html>
