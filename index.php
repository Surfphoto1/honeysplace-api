<?php
/**
 * Honey's Place Order Management System
 * PHP Integration with Honey's Place XML API
 * 
 * Features:
 * - Order submission with customer and item details
 * - Stock checking (single and bulk)
 * - Order tracking by reference number
 * - Dashboard with order statistics
 */

class HoneyApiClient {
    private $apiUrl = 'https://www.honeysplace.com/ws/HePws.asmx';
    private $account;
    private $password;
    
    public function __construct() {
        $this->account = $_ENV['HONEY_API_ACCOUNT'] ?? '';
        $this->password = $_ENV['HONEY_API_PASSWORD'] ?? '';
    }
    
    /**
     * Submit an order to Honey's Place
     */
    public function submitOrder($orderData) {
        $xml = $this->buildOrderXml($orderData);
        
        $soapEnvelope = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
               xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <OrderSubmission xmlns="http://tempuri.org/">
      <xmlRequest>' . htmlspecialchars($xml) . '</xmlRequest>
    </OrderSubmission>
  </soap:Body>
</soap:Envelope>';

        $response = $this->makeRequest($soapEnvelope, 'http://tempuri.org/OrderSubmission');
        return $this->parseOrderResponse($response);
    }
    
    /**
     * Check stock for a single SKU
     */
    public function checkStock($sku) {
        $xml = $this->buildStockCheckXml($sku);
        
        $soapEnvelope = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
               xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <StockCheck xmlns="http://tempuri.org/">
      <xmlRequest>' . htmlspecialchars($xml) . '</xmlRequest>
    </StockCheck>
  </soap:Body>
</soap:Envelope>';

        $response = $this->makeRequest($soapEnvelope, 'http://tempuri.org/StockCheck');
        return $this->parseStockResponse($response);
    }
    
    /**
     * Get order status by reference number
     */
    public function getOrderStatus($reference) {
        $xml = $this->buildOrderStatusXml($reference);
        
        $soapEnvelope = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
               xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <OrderStatus xmlns="http://tempuri.org/">
      <xmlRequest>' . htmlspecialchars($xml) . '</xmlRequest>
    </OrderStatus>
  </soap:Body>
</soap:Envelope>';

        $response = $this->makeRequest($soapEnvelope, 'http://tempuri.org/OrderStatus');
        return $this->parseOrderStatusResponse($response);
    }
    
    /**
     * Get available shipping methods
     */
    public function getShippingMethods() {
        return [
            ['value' => 'F001', 'label' => 'FedEx First Overnight', 'group' => 'FedEx'],
            ['value' => 'F002', 'label' => 'FedEx Priority Overnight', 'group' => 'FedEx'],
            ['value' => 'F003', 'label' => 'FedEx Standard Overnight', 'group' => 'FedEx'],
            ['value' => 'F004', 'label' => 'FedEx 2Day', 'group' => 'FedEx'],
            ['value' => 'F005', 'label' => 'FedEx Express Saver', 'group' => 'FedEx'],
            ['value' => 'F006', 'label' => 'FedEx Ground', 'group' => 'FedEx'],
            ['value' => 'F007', 'label' => 'FedEx Home Delivery', 'group' => 'FedEx'],
            ['value' => 'F008', 'label' => 'FedEx Smart Post', 'group' => 'FedEx'],
            ['value' => 'U001', 'label' => 'UPS Next Day Air', 'group' => 'UPS'],
            ['value' => 'U002', 'label' => 'UPS Next Day Air Saver', 'group' => 'UPS'],
            ['value' => 'U003', 'label' => 'UPS 2nd Day Air', 'group' => 'UPS'],
            ['value' => 'U004', 'label' => 'UPS 2nd Day Air A.M.', 'group' => 'UPS'],
            ['value' => 'U005', 'label' => 'UPS 3 Day Select', 'group' => 'UPS'],
            ['value' => 'U006', 'label' => 'UPS Ground', 'group' => 'UPS'],
            ['value' => 'U007', 'label' => 'UPS Sure Post', 'group' => 'UPS'],
            ['value' => 'P001', 'label' => 'USPS Priority Mail Express', 'group' => 'USPS'],
            ['value' => 'P002', 'label' => 'USPS Priority Mail', 'group' => 'USPS'],
            ['value' => 'P003', 'label' => 'USPS Ground Advantage', 'group' => 'USPS'],
            ['value' => 'P004', 'label' => 'USPS Media Mail', 'group' => 'USPS']
        ];
    }
    
    private function buildOrderXml($orderData) {
        $itemsXml = '';
        foreach ($orderData['items'] as $item) {
            $itemsXml .= sprintf(
                '<item><sku>%s</sku><qty>%d</qty></item>',
                htmlspecialchars($item['sku']),
                $item['quantity']
            );
        }
        
        return sprintf(
            '<order>
                <account>%s</account>
                <password>%s</password>
                <reference>%s</reference>
                <shipby>%s</shipby>
                <date>%s</date>
                <items>%s</items>
                <last>%s</last>
                <first>%s</first>
                <address1>%s</address1>
                <address2>%s</address2>
                <city>%s</city>
                <state>%s</state>
                <zip>%s</zip>
                <country>%s</country>
                <phone>%s</phone>
                <emailaddress>%s</emailaddress>
                <instructions>%s</instructions>
            </order>',
            htmlspecialchars($this->account),
            htmlspecialchars($this->password),
            htmlspecialchars($orderData['reference']),
            htmlspecialchars($orderData['shippingMethod']),
            date('Y-m-d H:i:s'),
            $itemsXml,
            htmlspecialchars($orderData['lastName']),
            htmlspecialchars($orderData['firstName']),
            htmlspecialchars($orderData['address1']),
            htmlspecialchars($orderData['address2'] ?? ''),
            htmlspecialchars($orderData['city']),
            htmlspecialchars($orderData['state']),
            htmlspecialchars($orderData['zip']),
            htmlspecialchars($orderData['country']),
            htmlspecialchars($orderData['phone']),
            htmlspecialchars($orderData['email']),
            htmlspecialchars($orderData['instructions'] ?? '')
        );
    }
    
    private function buildStockCheckXml($sku) {
        return sprintf(
            '<stock>
                <account>%s</account>
                <password>%s</password>
                <sku>%s</sku>
            </stock>',
            htmlspecialchars($this->account),
            htmlspecialchars($this->password),
            htmlspecialchars($sku)
        );
    }
    
    private function buildOrderStatusXml($reference) {
        return sprintf(
            '<orderstatus>
                <account>%s</account>
                <password>%s</password>
                <reference>%s</reference>
            </orderstatus>',
            htmlspecialchars($this->account),
            htmlspecialchars($this->password),
            htmlspecialchars($reference)
        );
    }
    
    private function makeRequest($soapEnvelope, $soapAction) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soapEnvelope);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: ' . $soapAction,
            'Content-Length: ' . strlen($soapEnvelope)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: {$httpCode}");
        }
        
        return $response;
    }
    
    private function parseOrderResponse($response) {
        $xml = simplexml_load_string($response);
        if (!$xml) {
            throw new Exception('Invalid XML response');
        }
        
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('ns', 'http://tempuri.org/');
        
        $result = $xml->xpath('//ns:OrderSubmissionResult');
        if (!$result) {
            throw new Exception('No result found in response');
        }
        
        $resultXml = simplexml_load_string($result[0]);
        if (!$resultXml) {
            throw new Exception('Invalid result XML');
        }
        
        return [
            'code' => (string)$resultXml->code,
            'reference' => (string)$resultXml->reference,
            'message' => $this->getErrorMessage((string)$resultXml->code)
        ];
    }
    
    private function parseStockResponse($response) {
        $xml = simplexml_load_string($response);
        if (!$xml) {
            throw new Exception('Invalid XML response');
        }
        
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('ns', 'http://tempuri.org/');
        
        $result = $xml->xpath('//ns:StockCheckResult');
        if (!$result) {
            throw new Exception('No result found in response');
        }
        
        $resultXml = simplexml_load_string($result[0]);
        if (!$resultXml) {
            throw new Exception('Invalid result XML');
        }
        
        return [
            'sku' => (string)$resultXml->sku,
            'qty' => (string)$resultXml->qty
        ];
    }
    
    private function parseOrderStatusResponse($response) {
        $xml = simplexml_load_string($response);
        if (!$xml) {
            throw new Exception('Invalid XML response');
        }
        
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('ns', 'http://tempuri.org/');
        
        $result = $xml->xpath('//ns:OrderStatusResult');
        if (!$result) {
            throw new Exception('No result found in response');
        }
        
        $resultXml = simplexml_load_string($result[0]);
        if (!$resultXml) {
            throw new Exception('Invalid result XML');
        }
        
        return [
            'reference' => (string)$resultXml->reference,
            'salesorder' => (string)$resultXml->salesorder,
            'orderdate' => (string)$resultXml->orderdate,
            'shipagent' => (string)$resultXml->shipagent,
            'shipservice' => (string)$resultXml->shipservice,
            'freightcost' => (string)$resultXml->freightcost,
            'trackingnumber1' => (string)$resultXml->trackingnumber1,
            'status' => (string)$resultXml->status
        ];
    }
    
    private function getErrorMessage($code) {
        $messages = [
            '200' => 'Order submitted successfully',
            '400' => 'Invalid request format',
            '401' => 'Authentication failed',
            '404' => 'Order not found',
            '500' => 'Internal server error'
        ];
        
        return $messages[$code] ?? 'Unknown error';
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_GET['action'] ?? '';
        
        $apiClient = new HoneyApiClient();
        
        switch ($action) {
            case 'submit-order':
                $result = $apiClient->submitOrder($input);
                echo json_encode($result);
                break;
                
            case 'check-stock':
                $result = $apiClient->checkStock($input['sku']);
                echo json_encode($result);
                break;
                
            case 'bulk-stock-check':
                $results = [];
                foreach ($input['skus'] as $sku) {
                    try {
                        $result = $apiClient->checkStock($sku);
                        $results[] = [
                            'sku' => $sku,
                            'quantity' => (int)$result['qty'],
                            'isAvailable' => (int)$result['qty'] > 0,
                            'status' => 'success'
                        ];
                    } catch (Exception $e) {
                        $results[] = [
                            'sku' => $sku,
                            'quantity' => 0,
                            'isAvailable' => false,
                            'status' => 'error',
                            'error' => $e->getMessage()
                        ];
                    }
                }
                echo json_encode($results);
                break;
                
            case 'track-order':
                $result = $apiClient->getOrderStatus($input['reference']);
                echo json_encode($result);
                break;
                
            case 'shipping-methods':
                $result = $apiClient->getShippingMethods();
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    exit;
}

// Serve the HTML interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honey's Place Order Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab:hover {
            background: #f0f0f0;
        }
        
        .tab.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .tab-content {
            display: none;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .order-items {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .order-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .order-item input {
            margin-bottom: 0;
        }
        
        .remove-item {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .stock-results {
            margin-top: 20px;
        }
        
        .stock-item {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stock-item.available {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-item.unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Honey's Place Order Management</h1>
            <p>Manage orders, check stock, and track shipments</p>
        </div>
        
        <div class="tabs">
            <div class="tab active" data-tab="create-order">Create Order</div>
            <div class="tab" data-tab="stock-check">Stock Check</div>
            <div class="tab" data-tab="track-order">Track Order</div>
        </div>
        
        <!-- Create Order Tab -->
        <div id="create-order" class="tab-content active">
            <h2>Create New Order</h2>
            <form id="orderForm">
                <div class="form-group">
                    <label for="reference">Order Reference *</label>
                    <input type="text" id="reference" name="reference" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name *</label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name *</label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address1">Address Line 1 *</label>
                    <input type="text" id="address1" name="address1" required>
                </div>
                
                <div class="form-group">
                    <label for="address2">Address Line 2</label>
                    <input type="text" id="address2" name="address2">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">State *</label>
                        <input type="text" id="state" name="state" required>
                    </div>
                    <div class="form-group">
                        <label for="zip">ZIP Code *</label>
                        <input type="text" id="zip" name="zip" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="country">Country *</label>
                        <input type="text" id="country" name="country" value="US" required>
                    </div>
                    <div class="form-group">
                        <label for="shippingMethod">Shipping Method *</label>
                        <select id="shippingMethod" name="shippingMethod" required>
                            <option value="">Select shipping method...</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="instructions">Special Instructions</label>
                    <textarea id="instructions" name="instructions" rows="3"></textarea>
                </div>
                
                <div class="order-items">
                    <h3>Order Items</h3>
                    <div id="orderItems">
                        <div class="order-item">
                            <input type="text" placeholder="SKU" name="itemSku[]" required>
                            <input type="number" placeholder="Quantity" name="itemQty[]" min="1" required>
                            <button type="button" class="remove-item" onclick="removeItem(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn" onclick="addItem()">Add Item</button>
                </div>
                
                <button type="submit" class="btn">Submit Order</button>
            </form>
        </div>
        
        <!-- Stock Check Tab -->
        <div id="stock-check" class="tab-content">
            <h2>Stock Check</h2>
            
            <div class="tabs">
                <div class="tab active" data-tab="single-stock">Single SKU</div>
                <div class="tab" data-tab="bulk-stock">Bulk Check</div>
            </div>
            
            <div id="single-stock" class="tab-content active">
                <form id="stockForm">
                    <div class="form-group">
                        <label for="stockSku">SKU</label>
                        <input type="text" id="stockSku" name="sku" required>
                    </div>
                    <button type="submit" class="btn">Check Stock</button>
                </form>
            </div>
            
            <div id="bulk-stock" class="tab-content">
                <form id="bulkStockForm">
                    <div class="form-group">
                        <label for="bulkSkus">SKUs (one per line)</label>
                        <textarea id="bulkSkus" name="skus" rows="10" placeholder="SKU1&#10;SKU2&#10;SKU3" required></textarea>
                    </div>
                    <button type="submit" class="btn">Check All Stock</button>
                </form>
            </div>
            
            <div id="stockResults" class="stock-results hidden">
                <h3>Stock Results</h3>
                <div id="stockResultsContent"></div>
            </div>
        </div>
        
        <!-- Track Order Tab -->
        <div id="track-order" class="tab-content">
            <h2>Track Order</h2>
            <form id="trackForm">
                <div class="form-group">
                    <label for="trackReference">Order Reference</label>
                    <input type="text" id="trackReference" name="reference" required>
                </div>
                <button type="submit" class="btn">Track Order</button>
            </form>
            
            <div id="trackResults" class="hidden">
                <h3>Order Status</h3>
                <div id="trackResultsContent"></div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Update tab appearance
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Update content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Load shipping methods
        fetch('?action=shipping-methods', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(methods => {
            const select = document.getElementById('shippingMethod');
            methods.forEach(method => {
                const option = document.createElement('option');
                option.value = method.value;
                option.textContent = method.label;
                select.appendChild(option);
            });
        });
        
        // Order form submission
        document.getElementById('orderForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const orderData = {
                reference: formData.get('reference'),
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                address1: formData.get('address1'),
                address2: formData.get('address2'),
                city: formData.get('city'),
                state: formData.get('state'),
                zip: formData.get('zip'),
                country: formData.get('country'),
                shippingMethod: formData.get('shippingMethod'),
                instructions: formData.get('instructions'),
                items: []
            };
            
            const skus = formData.getAll('itemSku[]');
            const qtys = formData.getAll('itemQty[]');
            
            for (let i = 0; i < skus.length; i++) {
                if (skus[i] && qtys[i]) {
                    orderData.items.push({
                        sku: skus[i],
                        quantity: parseInt(qtys[i])
                    });
                }
            }
            
            try {
                const response = await fetch('?action=submit-order', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(orderData)
                });
                
                const result = await response.json();
                
                if (result.error) {
                    showAlert('Error: ' + result.error, 'error');
                } else {
                    showAlert('Order submitted successfully! Reference: ' + result.reference, 'success');
                    this.reset();
                }
            } catch (error) {
                showAlert('Error submitting order: ' + error.message, 'error');
            }
        });
        
        // Stock check form
        document.getElementById('stockForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sku = formData.get('sku');
            
            try {
                const response = await fetch('?action=check-stock', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({sku: sku})
                });
                
                const result = await response.json();
                
                if (result.error) {
                    showAlert('Error: ' + result.error, 'error');
                } else {
                    displayStockResults([{
                        sku: result.sku,
                        quantity: parseInt(result.qty),
                        isAvailable: parseInt(result.qty) > 0,
                        status: 'success'
                    }]);
                }
            } catch (error) {
                showAlert('Error checking stock: ' + error.message, 'error');
            }
        });
        
        // Bulk stock check form
        document.getElementById('bulkStockForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const skusText = formData.get('skus');
            const skus = skusText.split('\n').map(s => s.trim()).filter(s => s);
            
            try {
                const response = await fetch('?action=bulk-stock-check', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({skus: skus})
                });
                
                const results = await response.json();
                displayStockResults(results);
            } catch (error) {
                showAlert('Error checking stock: ' + error.message, 'error');
            }
        });
        
        // Track order form
        document.getElementById('trackForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const reference = formData.get('reference');
            
            try {
                const response = await fetch('?action=track-order', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({reference: reference})
                });
                
                const result = await response.json();
                
                if (result.error) {
                    showAlert('Error: ' + result.error, 'error');
                } else {
                    displayTrackResults(result);
                }
            } catch (error) {
                showAlert('Error tracking order: ' + error.message, 'error');
            }
        });
        
        // Helper functions
        function addItem() {
            const container = document.getElementById('orderItems');
            const itemDiv = document.createElement('div');
            itemDiv.className = 'order-item';
            itemDiv.innerHTML = `
                <input type="text" placeholder="SKU" name="itemSku[]" required>
                <input type="number" placeholder="Quantity" name="itemQty[]" min="1" required>
                <button type="button" class="remove-item" onclick="removeItem(this)">Remove</button>
            `;
            container.appendChild(itemDiv);
        }
        
        function removeItem(button) {
            button.parentElement.remove();
        }
        
        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            const container = document.querySelector('.container');
            container.insertBefore(alert, container.firstChild.nextSibling);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        function displayStockResults(results) {
            const container = document.getElementById('stockResultsContent');
            container.innerHTML = '';
            
            results.forEach(result => {
                const div = document.createElement('div');
                div.className = `stock-item ${result.isAvailable ? 'available' : 'unavailable'}`;
                div.innerHTML = `
                    <span><strong>${result.sku}</strong></span>
                    <span>${result.isAvailable ? `${result.quantity} available` : 'Out of stock'}</span>
                `;
                container.appendChild(div);
            });
            
            document.getElementById('stockResults').classList.remove('hidden');
        }
        
        function displayTrackResults(result) {
            const container = document.getElementById('trackResultsContent');
            container.innerHTML = `
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Reference:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${result.reference || 'N/A'}</td></tr>
                    <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Sales Order:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${result.salesorder || 'N/A'}</td></tr>
                    <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Order Date:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${result.orderdate || 'N/A'}</td></tr>
                    <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Status:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${result.status || 'N/A'}</td></tr>
                    <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Ship Agent:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${result.shipagent || 'N/A'}</td></tr>
                    <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Ship Service:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${result.shipservice || 'N/A'}</td></tr>
                    <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Freight Cost:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${result.freightcost || 'N/A'}</td></tr>
                    <tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>Tracking Number:</strong></td><td style="padding: 8px; border: 1px solid #ddd;">${result.trackingnumber1 || 'N/A'}</td></tr>
                </table>
            `;
            
            document.getElementById('trackResults').classList.remove('hidden');
        }
    </script>
</body>
</html>