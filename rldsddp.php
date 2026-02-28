<?php
$page_title = 'Report Lost/Damaged Property';
require_once('includes/load.php');
page_require_level(1);

// Fetch issued properties from transactions table
$sql = "
    SELECT 
        t.id as transaction_id,
        t.PAR_No,
        t.ICS_No,
        t.transaction_date,
        t.status,
        t.quantity,
        t.qty_returned,
        t.qty_re_issued,
        t.employee_id,
        t.properties_id,
        p.property_no,
        p.article,
        p.description AS item_description,
        p.unit,
        p.unit_cost as acquisition_cost,
        CONCAT(e.first_name, ' ', e.last_name) as issued_to_name,
        e.position,
        o.office_name,
        CASE 
            WHEN t.status = 'Issued' AND t.qty_returned = 0 THEN 'Fully Issued'
            WHEN t.status = 'Issued' AND t.qty_returned > 0 AND t.qty_returned < t.quantity THEN 'Partially Returned'
            WHEN t.status = 'Partially Re-Issued' THEN 'Partially Re-Issued'
            ELSE t.status
        END as current_status
    FROM transactions t
    LEFT JOIN properties p ON t.item_id = p.id
    LEFT JOIN employees e ON t.employee_id = e.id
    LEFT JOIN offices o ON e.office = o.id
    WHERE t.transaction_type = 'issue'
      AND t.status IN ('Issued', 'Partially Returned', 'Partially Re-Issued')
      AND t.item_id IS NOT NULL
    ORDER BY t.transaction_date DESC
";

$issued_items = find_by_sql($sql);

?>
<?php include_once('layouts/header.php'); ?>

<style>
    .back-button {
        margin-bottom: 20px;
    }
    
    .back-button a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: #6c757d;
        color: white;
        padding: 10px 20px;
        border-radius: 4px;
        text-decoration: none;
        transition: background-color 0.3s;
    }
    
    .back-button a:hover {
        background-color: #5a6268;
    }
    
    .property-selection-section {
        margin-bottom: 30px;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        background-color: #f8f9fa;
    }
    
    .property-list {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        background-color: white;
    }
    
    .property-item {
        display: flex;
        align-items: flex-start;
        padding: 12px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .property-item:hover {
        background-color: #f0f8ff;
    }
    
    .property-item.selected {
        background-color: #e7f3ff;
        border-left: 4px solid #007bff;
    }
    
    .property-checkbox {
        margin-right: 10px;
        margin-top: 5px;
    }
    
    .property-details {
        flex-grow: 1;
    }
    
    .property-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 5px;
    }
    
    .property-number {
        font-weight: 600;
        color: #1a365d;
        font-size: 14px;
    }
    
    .property-type-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 500;
    }
    
    .badge-consumable {
        background-color: #e3f2fd;
        color: #1565c0;
    }
    
    .badge-semi-expendable {
        background-color: #f3e5f5;
        color: #7b1fa2;
    }
    
    .property-description {
        color: #666;
        font-size: 13px;
        margin-bottom: 5px;
        line-height: 1.4;
    }
    
    .property-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        font-size: 12px;
        color: #777;
        margin-top: 5px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .property-cost {
        color: #28a745;
        font-weight: 500;
        min-width: 100px;
        text-align: right;
        font-size: 14px;
    }
    
    .selection-actions {
        margin-top: 15px;
        display: flex;
        gap: 10px;
    }
    
    .selection-count {
        margin-top: 10px;
        color: #666;
        font-size: 14px;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
    
    /* Rest of your existing styles remain the same */
    .container {
        max-width: 1000px;
        margin: 0 auto;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    .header {
        background-color: #1a365d;
        color: white;
        padding: 20px 30px;
        text-align: center;
        border-bottom: 4px solid #e2a03f;
    }
    
    .header h1 {
        font-size: 24px;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .header .subtitle {
        font-size: 16px;
        opacity: 0.9;
    }
    
    .appendix-number {
        position: absolute;
        top: 20px;
        right: 30px;
        background-color: #2d4d7a;
        padding: 5px 15px;
        border-radius: 4px;
        font-weight: 600;
    }
    
    .form-container {
        padding: 30px;
    }
    
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eaeaea;
    }
    
    .form-section-title {
        font-size: 18px;
        color: #1a365d;
        margin-bottom: 20px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2a03f;
        font-weight: 600;
    }
    
    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 20px;
        gap: 20px;
    }
    
    .form-group {
        flex: 1;
        min-width: 250px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #444;
    }
    
    .form-group input, 
    .form-group select, 
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 15px;
        transition: border 0.3s;
    }
    
    .form-group input:focus, 
    .form-group select:focus, 
    .form-group textarea:focus {
        border-color: #1a365d;
        outline: none;
        box-shadow: 0 0 0 2px rgba(26, 54, 93, 0.1);
    }
    
    .status-section {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 30px;
        border: 1px solid #eaeaea;
    }
    
    .status-options {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin-top: 15px;
    }
    
    .status-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .status-option input[type="checkbox"] {
        width: 18px;
        height: 18px;
    }
    
    .status-option label {
        margin: 0;
        font-weight: 500;
    }
    
    .property-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    
    .property-table th {
        background-color: #1a365d;
        color: white;
        padding: 12px 15px;
        text-align: left;
        font-weight: 500;
    }
    
    .property-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eaeaea;
    }
    
    .property-table input {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .property-table tr:hover {
        background-color: #f9f9f9;
    }
    
    .add-row-btn {
        background-color: #2d7a3d;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s;
    }
    
    .add-row-btn:hover {
        background-color: #236532;
    }
    
    .remove-row-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: background-color 0.3s;
    }
    
    .remove-row-btn:hover {
        background-color: #c82333;
    }
    
    .signature-section {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin-top: 20px;
    }
    
    .signature-box {
        flex: 1;
        min-width: 250px;
    }
    
    .signature-box label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #444;
    }
    
    .signature-line {
        border-bottom: 1px solid #333;
        padding: 30px 0 10px;
        text-align: center;
        margin-bottom: 5px;
    }
    
    .signature-caption {
        text-align: center;
        font-size: 14px;
        color: #666;
        margin-top: 5px;
    }
    
    .notary-section {
        background-color: #f1f5f9;
        padding: 20px;
        border-radius: 6px;
        margin-top: 30px;
        border-left: 4px solid #1a365d;
    }
    
    .form-footer {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eaeaea;
        font-size: 14px;
        color: #666;
    }
    
    .footer-info {
        display: flex;
        gap: 30px;
    }
    
    .footer-info div {
        min-width: 100px;
    }
    
    .actions {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 30px;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-save {
        background-color: #1a365d;
        color: white;
    }
    
    .btn-save:hover {
        background-color: #14284d;
    }
    
    .btn-reset {
        background-color: #e2a03f;
        color: white;
    }
    
    .btn-reset:hover {
        background-color: #d18e2a;
    }
    
    .btn-print {
        background-color: #2d7a3d;
        color: white;
    }
    
    .btn-print:hover {
        background-color: #236532;
    }
    
    .btn-add-selected {
        background-color: #007bff;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }
    
    .btn-add-selected:hover {
        background-color: #0069d9;
    }
    
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background-color: #2d7a3d;
        color: white;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: none;
        z-index: 1000;
    }
    
    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
        }
        
        .header h1 {
            font-size: 20px;
        }
        
        .appendix-number {
            position: relative;
            top: 0;
            right: 0;
            display: inline-block;
            margin-top: 10px;
        }
        
        .signature-section, .form-footer {
            flex-direction: column;
            gap: 20px;
        }
        
        .property-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .property-cost {
            text-align: left;
            margin-top: 5px;
        }
    }
    
    @media print {
        body {
            background-color: white;
            padding: 0;
        }
        
        .container {
            box-shadow: none;
        }
        
        .actions, .add-row-btn, .remove-row-btn, .property-selection-section, .back-button {
            display: none !important;
        }
    }
</style>

<div class="back-button">
    <a href="issued_items.php">
        <i class="fas fa-arrow-left"></i> Back to Issued Items
    </a>
</div>

<div class="container">
    <div class="header">
        <div class="appendix-number">Appendix 75</div>
        <h1>REPORT OF LOST, STOLEN, DAMAGED OR DESTROYED PROPERTY</h1>
        <div class="subtitle">Government Property Accountability Form</div>
    </div>
    
    <div class="form-container">
        <!-- Property Selection Section -->
        <div class="property-selection-section">
            <h2 class="form-section-title">Select Issued Properties to Report</h2>
            <p>Select from currently issued properties that need to be reported as lost, stolen, damaged, or destroyed:</p>
            
            <div class="property-list" id="propertyList">
                <?php if(!empty($issued_items)): ?>
                    <?php foreach($issued_items as $item): ?>
                        <?php
                        // Calculate available quantity for reporting
                        $available_qty = $item['quantity'] - $item['qty_returned'];
                        
                        // Format acquisition cost
                        $acquisition_cost = isset($item['acquisition_cost']) ? number_format($item['acquisition_cost'], 2) : '0.00';
                        
                        // Get PAR or ICS number
                        $document_no = !empty($item['PAR_No']) ? $item['PAR_No'] : $item['ICS_No'];
                        ?>
                        
                        <div class="property-item" 
                             data-id="<?php echo $item['transaction_id']; ?>" 
                             data-number="<?php echo htmlspecialchars($item['property_no']); ?>"
                             data-article="<?php echo htmlspecialchars($item['article']); ?>"
                             data-description="<?php echo htmlspecialchars($item['item_description']); ?>"
                             data-cost="<?php echo $acquisition_cost; ?>"
                             data-available-qty="<?php echo $available_qty; ?>"
                             data-document-no="<?php echo htmlspecialchars($document_no); ?>"
                             data-issued-to="<?php echo htmlspecialchars($item['issued_to_name']); ?>"
                             data-office="<?php echo htmlspecialchars($item['office_name']); ?>"
                             data-position="<?php echo htmlspecialchars($item['position']); ?>"
                             data-quantity="<?php echo $item['quantity']; ?>"
                             data-unit="<?php echo htmlspecialchars($item['unit']); ?>"
                             data-issue-date="<?php echo $item['transaction_date']; ?>">
                            
                            <input type="checkbox" class="property-checkbox" id="property_<?php echo $item['transaction_id']; ?>">
                            
                            <div class="property-details">
                                <div class="property-header">
                                    <div class="property-number">
                                        <?php echo htmlspecialchars($item['property_no']); ?>
                                    </div>
                                    <div class="property-cost">
                                        ₱<?php echo $acquisition_cost; ?>
                                    </div>
                                </div>
                                
                                <div class="property-description">
                                    <strong><?php echo htmlspecialchars($item['article']); ?></strong>
                                    <?php if(!empty($item['item_description'])): ?>
                                        - <?php echo htmlspecialchars($item['item_description']); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-file-alt"></i>
                                        <span><strong>Document:</strong> <?php echo htmlspecialchars($document_no); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i>
                                        <span><strong>Issued to:</strong> <?php echo htmlspecialchars($item['issued_to_name']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-building"></i>
                                        <span><strong>Office:</strong> <?php echo htmlspecialchars($item['office_name']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-box"></i>
                                        <span><strong>Qty Issued:</strong> <?php echo $item['quantity'] . ' ' . $item['unit']; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-undo"></i>
                                        <span><strong>Qty Returned:</strong> <?php echo $item['qty_returned'] . ' ' . $item['unit']; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><strong>Issue Date:</strong> <?php echo date('M j, Y', strtotime($item['transaction_date'])); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-info-circle"></i>
                                        <span><strong>Status:</strong> <?php echo $item['current_status']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h4>No Issued Properties Found</h4>
                        <p>There are no currently issued properties available for reporting.</p>
                        <p class="text-muted">Issue some properties first before reporting them as lost/damaged.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="selection-count">
                Selected: <span id="selectedCount">0</span> properties
            </div>
            
            <div class="selection-actions">
                <button type="button" class="btn-add-selected" id="addSelectedBtn">
                    <i class="fas fa-plus"></i> Add Selected to Report
                </button>
                <button type="button" class="btn-add-selected" id="selectAllBtn">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <button type="button" class="btn-add-selected" id="deselectAllBtn" style="background-color: #6c757d;">
                    <i class="fas fa-times-circle"></i> Deselect All
                </button>
            </div>
        </div>
        
        <!-- Entity Information Section -->
        <div class="form-section">
            <h2 class="form-section-title">Entity Information</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="entityName">Entity Name</label>
                    <input type="text" id="entityName" placeholder="Enter entity name">
                </div>
                
                <div class="form-group">
                    <label for="fundCluster">Fund Cluster</label>
                    <input type="text" id="fundCluster" placeholder="Enter fund cluster">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="department">Department/Office</label>
                    <input type="text" id="department" placeholder="Enter department/office">
                </div>
                
                <div class="form-group">
                    <label for="rlsddpNo">RLSDDP No.</label>
                    <input type="text" id="rlsddpNo" placeholder="Enter RLSDDP number">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="accountableOfficer">Accountable Officer</label>
                    <input type="text" id="accountableOfficer" placeholder="Enter accountable officer name">
                </div>
                
                <div class="form-group">
                    <label for="rlsddpDate">RLSDDP Date</label>
                    <input type="date" id="rlsddpDate">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="designation">Designation</label>
                    <input type="text" id="designation" placeholder="Enter designation">
                </div>
                
                <div class="form-group">
                    <label for="parNo">PAR No.</label>
                    <input type="text" id="parNo" placeholder="Enter PAR number">
                </div>
            </div>
        </div>
        
        <!-- Police Notification Section -->
        <div class="form-section">
            <h2 class="form-section-title">Police Notification</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Police Notified</label>
                    <div style="display: flex; gap: 20px; margin-top: 8px;">
                        <div>
                            <input type="radio" id="policeYes" name="policeNotified" value="yes">
                            <label for="policeYes" style="display: inline; margin-left: 5px;">Yes</label>
                        </div>
                        <div>
                            <input type="radio" id="policeNo" name="policeNotified" value="no" checked>
                            <label for="policeNo" style="display: inline; margin-left: 5px;">No</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="policeStation">Police Station</label>
                    <input type="text" id="policeStation" placeholder="Enter police station name">
                </div>
                
                <div class="form-group">
                    <label for="policeDate">Date Reported</label>
                    <input type="date" id="policeDate">
                </div>
                
                <div class="form-group">
                    <label for="policeReportNo">Report No.</label>
                    <input type="text" id="policeReportNo" placeholder="Enter police report number">
                </div>
            </div>
        </div>
        
        <!-- Status of Property Section -->
        <div class="status-section">
            <h2 class="form-section-title">Status of Property</h2>
            <p>Check applicable box(es):</p>
            
            <div class="status-options">
                <div class="status-option">
                    <input type="checkbox" id="statusLost">
                    <label for="statusLost">Lost</label>
                </div>
                
                <div class="status-option">
                    <input type="checkbox" id="statusStolen">
                    <label for="statusStolen">Stolen</label>
                </div>
                
                <div class="status-option">
                    <input type="checkbox" id="statusDamaged">
                    <label for="statusDamaged">Damaged</label>
                </div>
                
                <div class="status-option">
                    <input type="checkbox" id="statusDestroyed">
                    <label for="statusDestroyed">Destroyed</label>
                </div>
            </div>
        </div>
        
        <!-- Property Details Table -->
        <div class="form-section">
            <h2 class="form-section-title">Property Details</h2>
            
            <table class="property-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Property No.</th>
                        <th style="width: 40%;">Description</th>
                        <th style="width: 10%;">Qty</th>
                        <th style="width: 20%;">Acquisition Cost</th>
                        <th style="width: 15%;">Action</th>
                    </tr>
                </thead>
                <tbody id="propertyTableBody">
                    <!-- Rows will be added dynamically -->
                </tbody>
            </table>
            
            <button type="button" class="add-row-btn" id="addPropertyRow">
                <i class="fas fa-plus"></i> Add Property Manually
            </button>
        </div>
        
        <!-- Circumstances Section -->
        <div class="form-section">
            <h2 class="form-section-title">Circumstances</h2>
            
            <div class="form-group">
                <label for="circumstances">Provide details about the circumstances of loss, theft, damage or destruction:</label>
                <textarea id="circumstances" rows="5" placeholder="Enter detailed circumstances..."></textarea>
            </div>
        </div>
        
        <!-- Certification Section -->
        <div class="form-section">
            <h2 class="form-section-title">Certification</h2>
            <p style="margin-bottom: 20px; font-style: italic;">I hereby certify that the item/s and circumstances stated above are true and correct.</p>
            
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-caption">Signature over Printed Name of the Accountable Officer</div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <label for="accountableOfficerSignature">Accountable Officer Name</label>
                        <input type="text" id="accountableOfficerSignature" placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="accountableOfficerDate">Date</label>
                        <input type="date" id="accountableOfficerDate">
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-caption">Signature over Printed Name of the Immediate Supervisor</div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <label for="supervisorName">Supervisor Name</label>
                        <input type="text" id="supervisorName" placeholder="Enter supervisor name">
                    </div>
                    
                    <div class="form-group">
                        <label for="supervisorDate">Date</label>
                        <input type="date" id="supervisorDate">
                    </div>
                </div>
            </div>
            
            <!-- Government ID Section -->
            <div class="form-row" style="margin-top: 20px;">
                <div class="form-group">
                    <label for="govId">Government Issued ID</label>
                    <input type="text" id="govId" placeholder="e.g. Driver's License, Passport">
                </div>
                
                <div class="form-group">
                    <label for="idNo">ID No.</label>
                    <input type="text" id="idNo" placeholder="Enter ID number">
                </div>
                
                <div class="form-group">
                    <label for="idDateIssued">Date Issued</label>
                    <input type="date" id="idDateIssued">
                </div>
            </div>
        </div>
        
        <!-- Notary Section -->
        <div class="notary-section">
            <h3>Notary Public Section</h3>
            <div class="form-row" style="margin-top: 15px;">
                <div class="form-group">
                    <label for="docNo">Doc. No.</label>
                    <input type="text" id="docNo" placeholder="Enter document number">
                </div>
                
                <div class="form-group">
                    <label for="pageNo">Page No.</label>
                    <input type="text" id="pageNo" placeholder="Enter page number">
                </div>
                
                <div class="form-group">
                    <label for="bookNo">Book No.</label>
                    <input type="text" id="bookNo" placeholder="Enter book number">
                </div>
                
                <div class="form-group">
                    <label for="seriesOf">Series of</label>
                    <input type="text" id="seriesOf" placeholder="Enter series">
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label for="notaryStatement">Notary Statement</label>
                <textarea id="notaryStatement" rows="3" placeholder="Enter notary statement..."></textarea>
            </div>
        </div>
        
        <!-- Form Footer -->
        <div class="form-footer">
            <div>Page 1</div>
            <div class="footer-info">
                <div>Doc. No.: <span id="docNoDisplay">_________</span></div>
                <div>Page No.: <span id="pageNoDisplay">_________</span></div>
                <div>Book No.: <span id="bookNoDisplay">_________</span></div>
                <div>Series of: <span id="seriesOfDisplay">_________</span></div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="actions">
            <button type="button" class="btn btn-reset" id="resetBtn">Reset Form</button>
            <button type="button" class="btn btn-save" id="saveBtn">Save Report</button>
            <button type="button" class="btn btn-print" id="printBtn">Print Form</button>
        </div>
    </div>
</div>

<!-- Notification -->
<div class="notification" id="notification">
    Form saved successfully!
</div>

<script>
    // Property selection functionality
    let selectedProperties = new Set();
    
    // Update selected count
    function updateSelectedCount() {
        document.getElementById('selectedCount').textContent = selectedProperties.size;
    }
    
    // Function to auto-fill form fields when a property is selected
    function autoFillFormFields(propertyItem) {
        const issuedTo = propertyItem.getAttribute('data-issued-to');
        const office = propertyItem.getAttribute('data-office');
        const position = propertyItem.getAttribute('data-position');
        const documentNo = propertyItem.getAttribute('data-document-no');
        
        // Auto-fill Accountable Officer (issued to person)
        const accountableOfficerField = document.getElementById('accountableOfficer');
        if (accountableOfficerField && !accountableOfficerField.value) {
            accountableOfficerField.value = issuedTo;
        }
        
        // Auto-fill Department/Office
        const departmentField = document.getElementById('department');
        if (departmentField && !departmentField.value) {
            departmentField.value = office;
        }
        
        // Auto-fill Designation (position)
        const designationField = document.getElementById('designation');
        if (designationField && !designationField.value) {
            designationField.value = position;
        }
        
        // Auto-fill PAR No. (use document number)
        const parNoField = document.getElementById('parNo');
        if (parNoField && !parNoField.value && documentNo) {
            parNoField.value = documentNo;
        }
        
        // Show notification that fields were auto-filled
        showNotification('Form fields have been auto-filled from the selected property.', 'info');
    }
    
    // Handle property item click
    document.querySelectorAll('.property-item').forEach(item => {
        const checkbox = item.querySelector('.property-checkbox');
        
        // Click on the whole item toggles checkbox
        item.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
        
        // Handle checkbox change
        checkbox.addEventListener('change', function() {
            const transactionId = item.getAttribute('data-id');
            
            if (this.checked) {
                selectedProperties.add(transactionId);
                item.classList.add('selected');
                
                // Auto-fill form fields when item is selected
                autoFillFormFields(item);
            } else {
                selectedProperties.delete(transactionId);
                item.classList.remove('selected');
            }
            
            updateSelectedCount();
        });
    });
    
    // Select all properties
    document.getElementById('selectAllBtn').addEventListener('click', function() {
        document.querySelectorAll('.property-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        });
    });
    
    // Deselect all properties
    document.getElementById('deselectAllBtn').addEventListener('click', function() {
        document.querySelectorAll('.property-checkbox').forEach(checkbox => {
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change'));
        });
    });
    
    // Add selected properties to the report table
    document.getElementById('addSelectedBtn').addEventListener('click', function() {
        const selectedItems = document.querySelectorAll('.property-item.selected');
        
        if (selectedItems.length === 0) {
            showNotification('Please select at least one property to add to the report.', 'error');
            return;
        }
        
        // Create a quantity input modal
        let quantityHtml = '';
        selectedItems.forEach((item, index) => {
            const propertyNo = item.getAttribute('data-number');
            const article = item.getAttribute('data-article');
            const description = item.getAttribute('data-description');
            const availableQty = parseInt(item.getAttribute('data-available-qty') || 1);
            const transactionId = item.getAttribute('data-id');
            const issuedTo = item.getAttribute('data-issued-to');
            const documentNo = item.getAttribute('data-document-no');
            const cost = item.getAttribute('data-cost');
            
            quantityHtml += `
                <div class="mb-3 border-bottom pb-3">
                    <h6 class="text-primary">${propertyNo}</h6>
                    <small class="text-muted d-block mb-2">${article} - ${description}</small>
                    <input type="hidden" name="transaction_ids[]" value="${transactionId}">
                    <input type="hidden" name="property_nos[]" value="${propertyNo}">
                    <input type="hidden" name="articles[]" value="${article}">
                    <input type="hidden" name="descriptions[]" value="${description}">
                    <input type="hidden" name="issued_to[]" value="${issuedTo}">
                    <input type="hidden" name="document_nos[]" value="${documentNo}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Quantity to Report</label>
                            <input type="number" 
                                   name="quantities[]" 
                                   class="form-control" 
                                   min="1" 
                                   max="${availableQty}" 
                                   value="${availableQty}"
                                   required>
                            <small class="text-muted">Maximum available: ${availableQty} units</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Cost (₱)</label>
                            <input type="text" 
                                   name="costs[]" 
                                   class="form-control" 
                                   value="${cost}"
                                   readonly>
                            <small class="text-muted">Original acquisition cost</small>
                        </div>
                    </div>
                </div>
            `;
        });
        
        // Show quantity selection modal
        const modalHtml = `
            <div class="modal fade" id="quantityModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Specify Property Details</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Specify the details for each selected property:</p>
                            <form id="quantityForm">
                                ${quantityHtml}
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmQuantitiesBtn">Add to Report</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
        quantityModal.show();
        
        // Handle confirm button
        document.getElementById('confirmQuantitiesBtn').addEventListener('click', function() {
            const form = document.getElementById('quantityForm');
            const formData = new FormData(form);
            const transactionIds = formData.getAll('transaction_ids[]');
            const propertyNos = formData.getAll('property_nos[]');
            const articles = formData.getAll('articles[]');
            const descriptions = formData.getAll('descriptions[]');
            const quantities = formData.getAll('quantities[]');
            const costs = formData.getAll('costs[]');
            const issuedTos = formData.getAll('issued_to[]');
            const documentNos = formData.getAll('document_nos[]');
            
            // Add each item to the table
            selectedItems.forEach((item, index) => {
                if (index < transactionIds.length) {
                    const propertyNo = propertyNos[index];
                    const article = articles[index];
                    const description = descriptions[index];
                    const quantity = quantities[index] || 1;
                    const cost = costs[index] || '0.00';
                    const transactionId = transactionIds[index];
                    const issuedTo = issuedTos[index];
                    const documentNo = documentNos[index];
                    
                    // Create full description
                    const fullDescription = `${article} - ${description}`;
                    
                    addPropertyToTable(propertyNo, fullDescription, cost, quantity, transactionId);
                    
                    // Uncheck and deselect the item
                    const checkbox = item.querySelector('.property-checkbox');
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
            
            // Close modal and remove from DOM
            quantityModal.hide();
            setTimeout(() => {
                document.getElementById('quantityModal').remove();
            }, 500);
            
            showNotification(`Added ${selectedItems.length} properties to report`, 'success');
        });
        
        // Remove modal from DOM when hidden
        document.getElementById('quantityModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    });
    
    // Add property row functionality (manual addition)
    document.getElementById('addPropertyRow').addEventListener('click', function() {
        addPropertyToTable('', '', '0.00', 1, '');
    });
    
    // Function to add a row to the property table
    function addPropertyToTable(propertyNo, description, cost, quantity = 1, transactionId = '') {
        const tableBody = document.getElementById('propertyTableBody');
        const newRow = document.createElement('tr');
        
        // Format cost if it's a number
        let formattedCost = cost;
        if (cost && !isNaN(parseFloat(cost))) {
            formattedCost = parseFloat(cost).toFixed(2);
            formattedCost = formattedCost.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        newRow.innerHTML = `
            <td>
                <input type="text" placeholder="e.g. PROP-001" class="property-no" value="${propertyNo || ''}">
                <input type="hidden" class="transaction-id" value="${transactionId || ''}">
            </td>
            <td><input type="text" placeholder="Enter property description" value="${description || ''}"></td>
            <td><input type="number" class="quantity-input" min="1" value="${quantity || 1}" placeholder="Qty"></td>
            <td><input type="text" placeholder="0.00" class="cost-input" value="${formattedCost}"></td>
            <td><button type="button" class="remove-row-btn">Remove</button></td>
        `;
        
        tableBody.appendChild(newRow);
        
        // Add event listener to the new remove button
        newRow.querySelector('.remove-row-btn').addEventListener('click', function() {
            tableBody.removeChild(newRow);
        });
        
        // Format cost input
        newRow.querySelector('.cost-input').addEventListener('blur', formatCurrency);
    }
    
    // Add event listeners to remove buttons in existing rows
    document.querySelectorAll('.remove-row-btn').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const tableBody = document.getElementById('propertyTableBody');
            tableBody.removeChild(row);
        });
    });
    
    // Format currency inputs
    document.querySelectorAll('.cost-input').forEach(input => {
        input.addEventListener('blur', formatCurrency);
    });
    
    function formatCurrency(event) {
        const input = event ? event.target : this;
        let value = input.value.replace(/[^0-9.]/g, '');
        
        if (value) {
            const num = parseFloat(value).toFixed(2);
            input.value = num.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
    }
    
    // Update footer with notary information
    function updateFooter() {
        document.getElementById('docNoDisplay').textContent = 
            document.getElementById('docNo').value || '_________';
        document.getElementById('pageNoDisplay').textContent = 
            document.getElementById('pageNo').value || '_________';
        document.getElementById('bookNoDisplay').textContent = 
            document.getElementById('bookNo').value || '_________';
        document.getElementById('seriesOfDisplay').textContent = 
            document.getElementById('seriesOf').value || '_________';
    }
    
    // Add event listeners to notary inputs
    document.querySelectorAll('#docNo, #pageNo, #bookNo, #seriesOf').forEach(input => {
        input.addEventListener('input', updateFooter);
    });
    
    // Form reset functionality
    document.getElementById('resetBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to reset the entire form? All data will be lost.')) {
            // Reset main form
            document.querySelectorAll('input, textarea').forEach(input => {
                if (input.type !== 'button' && input.type !== 'submit' && !input.classList.contains('property-checkbox')) {
                    input.value = '';
                }
            });
            
            // Reset property table
            const tableBody = document.getElementById('propertyTableBody');
            tableBody.innerHTML = '';
            
            // Reset checkboxes in property list
            document.querySelectorAll('.property-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            });
            
            // Reset radio buttons
            document.getElementById('policeNo').checked = true;
            document.getElementById('policeNo').dispatchEvent(new Event('change'));
            
            // Uncheck all status checkboxes
            document.querySelectorAll('.status-option input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            updateFooter();
            showNotification('Form has been reset.', 'info');
        }
    });
    
    // Form save functionality
    document.getElementById('saveBtn').addEventListener('click', function() {
        // Basic validation
        const entityName = document.getElementById('entityName').value;
        const accountableOfficer = document.getElementById('accountableOfficer').value;
        
        if (!entityName || !accountableOfficer) {
            showNotification('Please fill in required fields (Entity Name and Accountable Officer).', 'error');
            return;
        }
        
        // Check if at least one property is added
        const propertyRows = document.querySelectorAll('#propertyTableBody tr');
        if (propertyRows.length === 0) {
            showNotification('Please add at least one property to the report.', 'error');
            return;
        }
        
        // Check if at least one status is selected
        const statusCheckboxes = document.querySelectorAll('.status-option input[type="checkbox"]');
        let statusSelected = false;
        let selectedStatus = '';
        
        statusCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                statusSelected = true;
                selectedStatus = checkbox.id.replace('status', '');
            }
        });
        
        if (!statusSelected) {
            showNotification('Please select at least one status for the property.', 'error');
            return;
        }
        
        // Prepare form data for submission
        const formData = new FormData();
        
        // Add entity information
        formData.append('entity_name', document.getElementById('entityName').value);
        formData.append('department_office', document.getElementById('department').value);
        formData.append('fund_cluster', document.getElementById('fundCluster').value);
        formData.append('accountable_officer', document.getElementById('accountableOfficer').value);
        formData.append('designation', document.getElementById('designation').value);
        formData.append('rlsddp_no', document.getElementById('rlsddpNo').value);
        formData.append('rlsddp_date', document.getElementById('rlsddpDate').value);
        formData.append('par_no', document.getElementById('parNo').value);
        formData.append('par_date', document.getElementById('rlsddpDate').value); // Using same date as RLSDDP date
        
        // Add police notification
        const policeNotified = document.querySelector('input[name="policeNotified"]:checked').value;
        formData.append('police_notified', policeNotified);
        if (policeNotified === 'Yes') {
            formData.append('police_station', document.getElementById('policeStation').value);
            formData.append('police_report_date', document.getElementById('policeDate').value);
        }
        
        // Add status (only one can be selected according to DB enum)
        formData.append('status', selectedStatus);
        
        // Add circumstances
        formData.append('circumstances', document.getElementById('circumstances').value);
        
        // Add certification
        formData.append('accountable_officer_signature', document.getElementById('accountableOfficerSignature').value);
        formData.append('supervisor_signature', document.getElementById('supervisorName').value);
        formData.append('accountable_officer_date', document.getElementById('accountableOfficerDate').value);
        formData.append('supervisor_date', document.getElementById('supervisorDate').value);
        
        // Add government ID
        formData.append('gov_id_type', document.getElementById('govId').value);
        formData.append('gov_id_number', document.getElementById('idNo').value);
        formData.append('gov_id_date_issued', document.getElementById('idDateIssued').value);
        
        // Add notary information
        formData.append('notarized_date', new Date().toISOString().split('T')[0]);
        formData.append('page_no', document.getElementById('pageNo').value);
        formData.append('book_no', document.getElementById('bookNo').value);
        formData.append('series_no', document.getElementById('seriesOf').value);
        
        // Collect property items
        const propertyItems = [];
        document.querySelectorAll('#propertyTableBody tr').forEach((row, index) => {
            const inputs = row.querySelectorAll('input');
            if (inputs[0].value || inputs[1].value || inputs[2].value) {
                propertyItems.push({
                    property_no: inputs[0].value,
                    description: inputs[1].value,
                    quantity: row.querySelector('.quantity-input')?.value || '1',
                    acquisition_cost: inputs[3].value
                });
            }
        });
        
        // For now, we'll save only the first property (as per your table structure)
        // You might want to modify your table to support multiple properties
        if (propertyItems.length > 0) {
            const firstItem = propertyItems[0];
            formData.append('property_no', firstItem.property_no);
            formData.append('description', firstItem.description);
            formData.append('acquisition_cost', firstItem.acquisition_cost);
        }
        
        // Send data to server via AJAX
        fetch('save_property_report.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Report saved successfully!', 'success');
                // Redirect to reports list page
                setTimeout(() => { 
                    window.location.href = 'rlsddp_reports.php'; 
                }, 2000);
            } else {
                showNotification('Error saving report: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error saving report. Please try again.', 'error');
        });
    });
    
    // Print functionality
    document.getElementById('printBtn').addEventListener('click', function() {
        window.print();
    });
    
    // Show notification
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        
        // Set color based on type
        if (type === 'error') {
            notification.style.backgroundColor = '#dc3545';
        } else if (type === 'info') {
            notification.style.backgroundColor = '#17a2b8';
        } else {
            notification.style.backgroundColor = '#2d7a3d';
        }
        
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }
    
    // Initialize date fields with today's date
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('rlsddpDate').value = today;
    document.getElementById('accountableOfficerDate').value = today;
    document.getElementById('supervisorDate').value = today;
    document.getElementById('policeDate').value = today;
    document.getElementById('idDateIssued').value = today;
    
    // Show/hide police fields based on radio button selection
    document.querySelectorAll('input[name="policeNotified"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const policeFields = document.querySelectorAll('#policeStation, #policeDate, #policeReportNo');
            
            if (this.value === 'yes') {
                policeFields.forEach(field => {
                    field.disabled = false;
                    field.style.opacity = '1';
                });
            } else {
                policeFields.forEach(field => {
                    field.disabled = true;
                    field.style.opacity = '0.6';
                    field.value = '';
                });
            }
        });
    });
    
    // Initialize police fields based on default selection
    document.getElementById('policeNo').dispatchEvent(new Event('change'));
    
    // Initialize footer
    updateFooter();
</script>

<?php include_once('layouts/footer.php'); ?>