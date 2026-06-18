<?php
require_once 'backend/config/db.php';
$active_page = 'contact';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact & Support — CarBazar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        :root { --gold: #f0c040; --navy: #1a1a2e; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
        .page-hero {
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 60%, #16213e 100%);
            padding: 60px 0 40px;
            color: #fff;
        }
        .page-hero h1 { font-weight: 800; }
        .page-hero p  { opacity: .75; }
        .tab-pill {
            display: inline-flex; gap: 8px; flex-wrap: wrap;
            background: rgba(255,255,255,.08);
            border-radius: 50px; padding: 6px;
        }
        .tab-pill a {
            padding: 8px 20px; border-radius: 40px;
            color: rgba(255,255,255,.7); text-decoration: none;
            font-weight: 600; font-size: .85rem;
            transition: all .2s;
        }
        .tab-pill a:hover { color: #fff; background: rgba(255,255,255,.1); }
        .tab-pill a.active { background: var(--gold); color: var(--navy); }

        .section-card {
            background: #fff; border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.07);
            padding: 36px 40px; margin-bottom: 32px;
        }
        .section-card h2 {
            font-weight: 800; font-size: 1.5rem;
            color: var(--navy); margin-bottom: 6px;
        }
        .section-card .lead { color: #6b7280; font-size: .95rem; margin-bottom: 24px; }

        /* Contact cards */
        .contact-box {
            border-radius: 14px; padding: 28px 20px;
            text-align: center; height: 100%;
            border: 1.5px solid #e5e7eb;
            transition: box-shadow .2s, transform .2s;
        }
        .contact-box:hover { box-shadow: 0 8px 28px rgba(0,0,0,.1); transform: translateY(-4px); }
        .contact-box .icon-wrap {
            width: 60px; height: 60px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 14px;
        }
        .contact-box h5 { font-weight: 700; margin-bottom: 8px; }
        .contact-box p  { color: #6b7280; font-size: .9rem; margin: 0; line-height: 1.7; }

        /* FAQ accordion */
        .faq-item { border: 1.5px solid #e5e7eb; border-radius: 12px; margin-bottom: 10px; overflow: hidden; }
        .faq-question {
            width: 100%; text-align: left; background: #fff;
            border: none; padding: 16px 20px;
            font-weight: 600; font-size: .95rem; color: var(--navy);
            display: flex; justify-content: space-between; align-items: center;
            cursor: pointer; transition: background .15s;
        }
        .faq-question:hover { background: #fffbeb; }
        .faq-question.open  { background: #fffbeb; color: #b45309; }
        .faq-question .faq-icon { transition: transform .25s; font-size: .8rem; color: var(--gold); }
        .faq-question.open .faq-icon { transform: rotate(180deg); }
        .faq-answer {
            display: none; padding: 0 20px 16px;
            color: #4b5563; font-size: .9rem; line-height: 1.7;
            background: #fffbeb;
        }
        .faq-answer.open { display: block; }

        /* Policy sections */
        .policy-section h4 { font-weight: 700; color: var(--navy); margin-top: 28px; margin-bottom: 8px; }
        .policy-section p, .policy-section li { color: #4b5563; font-size: .93rem; line-height: 1.8; }
        .policy-section ul { padding-left: 20px; }

        /* Contact form */
        .form-control:focus, .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(240,192,64,.15);
        }
        .btn-gold {
            background: linear-gradient(135deg, #f0c040, #e0a800);
            color: var(--navy); font-weight: 700; border: none;
            padding: 12px 32px; border-radius: 10px;
            transition: all .2s;
        }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(240,192,64,.4); color: var(--navy); }

        footer a:hover { color: var(--gold) !important; }
    </style>
</head>
<body>

<?php require_once 'includes/navbar.php'; ?>

<!-- PAGE HERO -->
<div class="page-hero">
    <div class="container text-center">
        <span class="badge bg-warning text-dark px-3 py-2 mb-3" style="border-radius:20px;font-size:.8rem;letter-spacing:1px;">SUPPORT CENTER</span>
        <h1 class="mb-2">How Can We Help You?</h1>
        <p class="mb-4">Contact us, read our policies, or find quick answers below</p>
        <!-- Tab Pills → Dropdown Select -->
        <div class="tab-pill">
            <select onchange="if(this.value) { document.querySelector(this.value).scrollIntoView({behavior:'smooth',block:'start'}); this.value=''; }"
                style="background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.25);color:#fff;border-radius:40px;padding:10px 20px;font-size:.88rem;font-weight:600;cursor:pointer;outline:none;appearance:none;-webkit-appearance:none;background-image:url('data:image/svg+xml;utf8,<svg fill=\'white\' height=\'16\' viewBox=\'0 0 24 24\' width=\'16\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M7 10l5 5 5-5z\'/></svg>');background-repeat:no-repeat;background-position:right 14px center;padding-right:38px;">
                <option value="" style="background:#1a1a2e;color:#fff;">📋 Jump to Section...</option>
                <option value="#contact"  style="background:#1a1a2e;color:#fff;">📞 Contact Us</option>
                <option value="#returns"  style="background:#1a1a2e;color:#fff;">↩️ Returns Policy</option>
                <option value="#warranty" style="background:#1a1a2e;color:#fff;">🛡️ Warranty</option>
                <option value="#faq"      style="background:#1a1a2e;color:#fff;">❓ FAQs</option>
                <option value="#privacy"  style="background:#1a1a2e;color:#fff;">🔒 Privacy Policy</option>
            </select>
        </div>
    </div>
</div>

<div class="container py-5">

    <!-- ===== CONTACT ===== -->
    <div id="contact" class="section-card">
        <h2><i class="fas fa-headset me-2" style="color:var(--gold)"></i>Contact Us</h2>
        <p class="lead">We're available 7 days a week. Reach out through any channel below.</p>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="contact-box">
                    <div class="icon-wrap" style="background:#e8f0fe;"><i class="fas fa-map-marker-alt text-primary"></i></div>
                    <h5>Address</h5>
                    <p>123 Car Lane, Auto City<br>Vehari, Punjab<br>Pakistan</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-box">
                    <div class="icon-wrap" style="background:#e8f5e9;"><i class="fas fa-phone-alt text-success"></i></div>
                    <h5>Phone</h5>
                    <p>+92 304 0369392<br>+92 304 0369394<br>Available 24/7</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-box">
                    <div class="icon-wrap" style="background:#fffde7;"><i class="fas fa-envelope" style="color:var(--gold)"></i></div>
                    <h5>Email</h5>
                    <p>support@carbazar.com<br>sales@carbazar.com<br>seller@carbazar.com</p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <h4 class="fw-bold mb-3" style="color:var(--navy)">Send Us a Message</h4>
        <form onsubmit="submitContactForm(event)">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-600">Your Name</label>
                    <input type="text" class="form-control" placeholder="Muhammad Ali" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600">Email Address</label>
                    <input type="email" class="form-control" placeholder="you@example.com" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600">Phone (optional)</label>
                    <input type="tel" class="form-control" placeholder="+92 3XX XXXXXXX">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-600">Subject</label>
                    <select class="form-select">
                        <option>General Inquiry</option>
                        <option>Order Issue</option>
                        <option>Returns & Refunds</option>
                        <option>Warranty Claim</option>
                        <option>Seller Support</option>
                        <option>Technical Problem</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-600">Message</label>
                    <textarea class="form-control" rows="5" placeholder="Describe your issue or question..." required></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-gold">
                        <i class="fas fa-paper-plane me-2"></i>Send Message
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ===== RETURNS POLICY ===== -->
    <div id="returns" class="section-card policy-section">
        <h2><i class="fas fa-undo-alt me-2" style="color:var(--gold)"></i>Returns Policy</h2>
        <p class="lead">We want you to be 100% satisfied with your purchase.</p>

        <h4>Spare Parts — Return Window</h4>
        <p>You may return unused spare parts within <strong>7 days</strong> of delivery. Items must be in original packaging, unused, and in the same condition as received.</p>

        <h4>Eligible Items</h4>
        <ul>
            <li>Wrong part delivered (different from order)</li>
            <li>Defective or damaged on arrival</li>
            <li>Part does not fit the specified vehicle model</li>
        </ul>

        <h4>Non-Returnable Items</h4>
        <ul>
            <li>Installed or used parts</li>
            <li>Electrical components once opened</li>
            <li>Items without original packaging</li>
            <li>Parts purchased during clearance sales</li>
        </ul>

        <h4>Cars</h4>
        <p>Cars listed on CarBazar are sold by individual sellers. Returns are subject to mutual agreement between buyer and seller. CarBazar facilitates communication but is not responsible for private sale disputes.</p>

        <h4>How to Initiate a Return</h4>
        <p>Email us at <strong>support@carbazar.com</strong> with your order number, reason for return, and photos of the item. Our team will respond within <strong>24 hours</strong>.</p>

        <h4>Refund Timeline</h4>
        <p>Once the returned item is received and inspected, refunds are processed within <strong>3–5 business days</strong> to your original payment method.</p>
    </div>

    <!-- ===== WARRANTY ===== -->
    <div id="warranty" class="section-card policy-section">
        <h2><i class="fas fa-shield-alt me-2" style="color:var(--gold)"></i>Warranty Policy</h2>
        <p class="lead">We stand behind the quality of products sold on CarBazar.</p>

        <h4>Spare Parts Warranty</h4>
        <p>All spare parts sold through CarBazar carry a <strong>30-day warranty</strong> against manufacturing defects from the date of delivery.</p>

        <h4>What's Covered</h4>
        <ul>
            <li>Manufacturing defects</li>
            <li>Parts that fail under normal use within the warranty period</li>
            <li>Incorrect specifications compared to product listing</li>
        </ul>

        <h4>What's Not Covered</h4>
        <ul>
            <li>Damage caused by improper installation</li>
            <li>Normal wear and tear</li>
            <li>Damage from accidents, misuse, or modifications</li>
            <li>Parts installed by uncertified mechanics</li>
        </ul>

        <h4>Used Cars</h4>
        <p>Used cars are sold "as-is" by private sellers unless the seller explicitly states a warranty. CarBazar recommends a pre-purchase inspection by a certified mechanic before buying any used vehicle.</p>

        <h4>Claiming Warranty</h4>
        <p>Contact us at <strong>support@carbazar.com</strong> with your order ID and a description of the defect. Include photos or a short video if possible. We'll arrange a replacement or refund within <strong>5 business days</strong>.</p>
    </div>

    <!-- ===== FAQs ===== -->
    <div id="faq" class="section-card">
        <h2><i class="fas fa-question-circle me-2" style="color:var(--gold)"></i>Frequently Asked Questions</h2>
        <p class="lead">Quick answers to the most common questions.</p>

        <?php
        $faqs = [
            ["How do I buy a car on CarBazar?", "Browse the Cars section, click on any listing to view details, and use the 'Contact Seller' button to get in touch directly. You can also use the search filters to narrow down by brand, city, and budget."],
            ["How do I sell my car?", "Click 'Sell' in the navbar, fill in your car details, upload photos, and submit. Your listing will go live after a quick review. You need a free account to list."],
            ["Are the spare parts genuine?", "All sellers on CarBazar are verified. Product listings must accurately describe the condition (new/used/OEM/aftermarket). If you receive something different from the listing, you are covered by our Returns Policy."],
            ["How do I track my order?", "Go to 'My Orders' in the navbar after logging in. You'll see the status of all your orders there."],
            ["Can I add items to a wishlist?", "Yes! Click the heart icon on any car or part to save it to your wishlist. You need to be logged in."],
            ["What payment methods are accepted?", "Currently CarBazar supports Cash on Delivery (COD) for spare parts. For cars, payment is arranged directly between buyer and seller."],
            ["How do I become a seller?", "Register for a free account and select 'Seller' during signup, or upgrade your existing account from your profile settings."],
            ["What if I receive a wrong or damaged item?", "Contact us within 7 days at support@carbazar.com with your order number and photos. We'll arrange a return and refund or replacement."],
            ["Is my personal information safe?", "Yes. We never sell your data to third parties. Read our full Privacy Policy below for details."],
            ["How do I contact a seller directly?", "On any car listing page, click 'Contact Seller' to see the seller's phone number (login required)."],
        ];
        foreach ($faqs as $i => $faq): ?>
        <div class="faq-item">
            <button class="faq-question" onclick="toggleFaq(this)">
                <span><?= ($i+1) ?>. <?= htmlspecialchars($faq[0]) ?></span>
                <i class="fas fa-chevron-down faq-icon"></i>
            </button>
            <div class="faq-answer"><?= htmlspecialchars($faq[1]) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== PRIVACY POLICY ===== -->
    <div id="privacy" class="section-card policy-section">
        <h2><i class="fas fa-lock me-2" style="color:var(--gold)"></i>Privacy Policy</h2>
        <p class="lead">Last updated: May 2026. Your privacy matters to us.</p>

        <h4>Information We Collect</h4>
        <ul>
            <li><strong>Account info:</strong> Name, email, phone number when you register</li>
            <li><strong>Listing info:</strong> Car/part details, photos you upload</li>
            <li><strong>Transaction info:</strong> Orders, cart, and wishlist activity</li>
            <li><strong>Usage data:</strong> Pages visited, search queries (anonymized)</li>
        </ul>

        <h4>How We Use Your Information</h4>
        <ul>
            <li>To process orders and facilitate buyer-seller communication</li>
            <li>To send order confirmations and support responses</li>
            <li>To improve our platform and user experience</li>
            <li>To prevent fraud and ensure platform security</li>
        </ul>

        <h4>Information Sharing</h4>
        <p>We do <strong>not</strong> sell, rent, or trade your personal information to third parties. Your phone number is only visible to logged-in users on car listing pages to facilitate genuine inquiries.</p>

        <h4>Data Security</h4>
        <p>We use industry-standard security measures including encrypted connections (HTTPS), hashed passwords, and regular security audits to protect your data.</p>

        <h4>Cookies</h4>
        <p>CarBazar uses session cookies to keep you logged in and remember your cart. We do not use third-party tracking cookies.</p>

        <h4>Your Rights</h4>
        <ul>
            <li>Request a copy of your personal data</li>
            <li>Request deletion of your account and data</li>
            <li>Opt out of promotional emails at any time</li>
        </ul>

        <h4>Contact for Privacy</h4>
        <p>For any privacy-related concerns, email us at <strong>support@carbazar.com</strong>. We respond within 48 hours.</p>
    </div>

</div>

<!-- FOOTER -->
<?php
// Reuse footer from index — inline it here
?>
<footer style="background:linear-gradient(135deg,#0f0f1a 0%,#1a1a2e 60%,#16213e 100%);color:rgba(255,255,255,.75);padding:48px 0 0;">
    <div class="container">
        <div class="row g-4 pb-4" style="border-bottom:1px solid rgba(255,255,255,.1)">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div style="width:36px;height:36px;background:linear-gradient(135deg,#f0c040,#e0a800);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#1a1a2e;font-size:1rem;"><i class="fas fa-car"></i></div>
                    <span style="font-size:1.2rem;font-weight:800;color:#fff;">CarBazar</span>
                </div>
                <p style="font-size:.88rem;line-height:1.7;">Pakistan's trusted marketplace for used cars &amp; genuine spare parts.</p>
            </div>
            <div class="col-lg-2 col-6">
                <h6 style="color:#f0c040;font-weight:700;font-size:.8rem;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Quick Links</h6>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
                    <li><a href="index.php" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">Home</a></li>
                    <li><a href="all-cars.php" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">Cars For Sale</a></li>
                    <li><a href="all-parts.php" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">Spare Parts</a></li>
                    <li><a href="sell.php" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">Sell on CarBazar</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-6">
                <h6 style="color:#f0c040;font-weight:700;font-size:.8rem;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Support</h6>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px;">
                    <li><a href="contact.php#contact"  style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">Contact Us</a></li>
                    <li><a href="contact.php#returns"  style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">Returns Policy</a></li>
                    <li><a href="contact.php#warranty" style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">Warranty</a></li>
                    <li><a href="contact.php#faq"      style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">FAQs</a></li>
                    <li><a href="contact.php#privacy"  style="color:rgba(255,255,255,.7);text-decoration:none;font-size:.88rem;">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 style="color:#f0c040;font-weight:700;font-size:.8rem;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px;">Get In Touch</h6>
                <div style="display:flex;flex-direction:column;gap:10px;font-size:.88rem;">
                    <div><i class="fas fa-map-marker-alt me-2" style="color:#f0c040;width:16px;"></i>123 Car Lane, Vehari, Pakistan</div>
                    <div><i class="fas fa-phone-alt me-2" style="color:#f0c040;width:16px;"></i>+92 304 0369392</div>
                    <div><i class="fas fa-envelope me-2" style="color:#f0c040;width:16px;"></i>support@carbazar.com</div>
                    <div><i class="fas fa-clock me-2" style="color:#f0c040;width:16px;"></i>Mon–Sat: 9AM–9PM</div>
                </div>
            </div>
        </div>
        <div style="padding:20px 0;text-align:center;font-size:.83rem;color:rgba(255,255,255,.45);">
            &copy; 2026 <strong style="color:rgba(255,255,255,.7)">CarBazar</strong>. All rights reserved. &middot;
            <a href="contact.php#privacy" style="color:rgba(255,255,255,.5);text-decoration:none;">Privacy</a> &middot;
            <a href="contact.php#returns" style="color:rgba(255,255,255,.5);text-decoration:none;">Returns</a>
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
// FAQ toggle
function toggleFaq(btn) {
    var answer = btn.nextElementSibling;
    var isOpen = answer.classList.contains('open');
    // Close all
    document.querySelectorAll('.faq-answer').forEach(function(a){ a.classList.remove('open'); });
    document.querySelectorAll('.faq-question').forEach(function(b){ b.classList.remove('open'); });
    // Open clicked if it was closed
    if (!isOpen) {
        answer.classList.add('open');
        btn.classList.add('open');
    }
}

// Contact form submit
function submitContactForm(e) {
    e.preventDefault();
    var btn = e.target.querySelector('button[type=submit]');
    btn.innerHTML = '<i class="fas fa-check me-2"></i>Message Sent!';
    btn.style.background = '#22c55e';
    btn.style.color = '#fff';
    btn.disabled = true;
    setTimeout(function(){
        btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Message';
        btn.style.background = '';
        btn.style.color = '';
        btn.disabled = false;
        e.target.reset();
    }, 3000);
}

// Smooth scroll for tab pills — now handled by select onchange inline

// Highlight active section on scroll — update select value
window.addEventListener('scroll', function() {
    var sections = ['contact','returns','warranty','faq','privacy'];
    var scrollY = window.scrollY + 120;
    var sel = document.querySelector('.tab-pill select');
    sections.forEach(function(id) {
        var el = document.getElementById(id);
        if (el && el.offsetTop <= scrollY && (el.offsetTop + el.offsetHeight) > scrollY) {
            if (sel) sel.value = '#' + id;
        }
    });
});
</script>
</body>
</html>
