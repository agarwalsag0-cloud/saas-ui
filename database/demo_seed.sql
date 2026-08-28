-- Demo data for Multi-Business Subscription Platform
-- Import after install.sql only if you want demo/testing records. All demo user passwords are: password

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE activity_logs;
TRUNCATE TABLE notifications;
TRUNCATE TABLE order_status_history;
TRUNCATE TABLE enquiry_status_history;
TRUNCATE TABLE orders;
TRUNCATE TABLE enquiries;
TRUNCATE TABLE customers;
TRUNCATE TABLE offers;
TRUNCATE TABLE listings;
TRUNCATE TABLE categories;
TRUNCATE TABLE payments;
TRUNCATE TABLE business_subscriptions;
TRUNCATE TABLE subscription_plan_features;
TRUNCATE TABLE subscription_plans;
TRUNCATE TABLE business_settings;
TRUNCATE TABLE users;
TRUNCATE TABLE businesses;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO subscription_plans (id, name, description, billing_cycle, price, monthly_price, yearly_price, currency, features, is_active, sort_order, created_at, updated_at) VALUES
(1, 'Basic Monthly', 'Demo configurable plan with catalog/enquiry basics but no public website access.', 'monthly', 1499.00, 1499.00, 14990.00, 'INR', '[]', 1, 1, NOW(), NOW()),
(2, 'Growth Monthly', 'Demo configurable plan with offers, orders, notifications and richer website controls.', 'monthly', 2999.00, 2999.00, 29990.00, 'INR', '[]', 1, 2, NOW(), NOW()),
(3, 'Annual Pro', 'Demo configurable plan for established businesses with yearly pricing and additional premium-ready modules.', 'yearly', 24999.00, 2499.00, 24999.00, 'INR', '[]', 1, 3, NOW(), NOW());

-- Demo plan-feature assignments. Feature definitions come from platform_features; plans only reference them.
INSERT INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at)
SELECT 1, id, 1,
       CASE identifier
           WHEN 'product_management' THEN '{"max_items":25}'
           WHEN 'service_management' THEN '{"max_items":25}'
           ELSE NULL
       END,
       NOW(), NOW()
FROM platform_features
WHERE identifier IN ('business_profile','categories','product_management','service_management','enquiries','customer_management','dashboard_analytics','notifications');

INSERT INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at)
SELECT 2, id, 1,
       CASE identifier
           WHEN 'product_management' THEN '{"max_items":150}'
           WHEN 'service_management' THEN '{"max_items":150}'
           ELSE NULL
       END,
       NOW(), NOW()
FROM platform_features
WHERE identifier IN ('business_profile','public_website','website_customization','custom_branding','basic_seo','categories','product_management','service_management','featured_listings','offers','enquiries','customer_management','lead_management','orders','booking_requests','service_requests','dashboard_analytics','notifications','activity_tracking');

INSERT INTO subscription_plan_features (plan_id, feature_id, enabled, limits_json, created_at, updated_at)
SELECT 3, id, 1, NULL, NOW(), NOW()
FROM platform_features
WHERE identifier IN ('business_profile','public_website','website_customization','custom_branding','basic_seo','categories','product_management','service_management','featured_listings','inventory','offers','enquiries','customer_management','lead_management','whatsapp_button','orders','booking_requests','service_requests','dashboard_analytics','advanced_analytics','notifications','customer_history','activity_tracking','custom_domain','email_automation','sms_automation','ai_tools');

INSERT INTO businesses (id, name, slug, category, description, phone, email, address, city, state, country, website, social_links, timings, logo_path, cover_path, status, approved_at, created_at, updated_at) VALUES
(1, 'Lucky Tour & Travel', 'lucky-tour-travel', 'Travel Agency', 'Tours, pilgrimage packages, cab services, hotel assistance, visa support and custom travel planning.', '+91 98765 43210', 'hello@luckytour.test', 'Fatehabad Road, Near Taj East Gate', 'Agra', 'Uttar Pradesh', 'India', 'https://example.com/lucky', '{"whatsapp":"https://wa.me/919876543210","instagram":"https://instagram.com/example"}', 'Mon-Sat: 10:00 AM - 8:00 PM\nSunday: 11:00 AM - 4:00 PM', NULL, NULL, 'active', NOW(), DATE_SUB(NOW(), INTERVAL 40 DAY), NOW()),
(2, 'ElectroHub Electronics', 'electrohub-electronics', 'Electronics Retail', 'Smartphones, laptops, accessories, installation services and seasonal offers.', '+91 99887 76655', 'sales@electrohub.test', 'MG Road Market', 'Agra', 'Uttar Pradesh', 'India', 'https://example.com/electrohub', '{"whatsapp":"https://wa.me/919988776655"}', 'Daily: 10:30 AM - 9:00 PM', NULL, NULL, 'active', NOW(), DATE_SUB(NOW(), INTERVAL 25 DAY), NOW()),
(3, 'CalmCare Salon', 'calmcare-salon', 'Salon & Wellness', 'Demo pending tenant for approval workflow testing.', '+91 90000 11111', 'info@calmcare.test', 'Civil Lines', 'Agra', 'Uttar Pradesh', 'India', NULL, NULL, 'Mon-Sun: 10:00 AM - 7:00 PM', NULL, NULL, 'pending', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
(4, 'SpiceLeaf Restaurant', 'spiceleaf-restaurant', 'Restaurant', 'Demo active business with expired subscription to test access restrictions.', '+91 91111 22222', 'orders@spiceleaf.test', 'Sanjay Place', 'Agra', 'Uttar Pradesh', 'India', NULL, NULL, 'Daily: 12:00 PM - 11:00 PM', NULL, NULL, 'active', NOW(), DATE_SUB(NOW(), INTERVAL 300 DAY), NOW()),
(5, 'NorthStar Consulting', 'northstar-consulting', 'Consulting', 'Demo suspended tenant for suspension workflow testing.', '+91 92222 33333', 'hello@northstar.test', 'Remote', 'Delhi', 'Delhi', 'India', NULL, NULL, 'By appointment', NULL, NULL, 'suspended', NOW(), DATE_SUB(NOW(), INTERVAL 180 DAY), NOW());

UPDATE businesses SET tagline = 'Travel plans, pilgrimages and cab bookings made simple' WHERE id = 1;
UPDATE businesses SET tagline = 'Latest gadgets, honest pricing and reliable support' WHERE id = 2;
UPDATE businesses SET tagline = 'Salon and wellness appointments for modern customers' WHERE id = 3;
UPDATE businesses SET tagline = 'Fresh meals and family combos' WHERE id = 4;
UPDATE businesses SET tagline = 'Practical business consulting for growing teams' WHERE id = 5;

INSERT INTO business_settings (business_id, theme_preset, layout_style, background_style, button_style, text_style, primary_color, secondary_color, theme_color, accent_color, section_visibility, seo_title, seo_description, portal_settings, created_at, updated_at) VALUES
(1, 'modern', 'showcase', 'gradient', 'pill', 'modern', '#2563eb', '#0f172a', '#2563eb', '#f97316', NULL, 'Lucky Tour & Travel - Tours, Visa and Cab Services', 'Explore tour packages, visa documentation and cab booking services from Lucky Tour & Travel in Agra.', NULL, NOW(), NOW()),
(2, 'bold', 'classic', 'soft', 'rounded', 'modern', '#7c3aed', '#111827', '#7c3aed', '#06b6d4', NULL, 'ElectroHub Electronics - Mobiles, Laptops and Accessories', 'Shop demo electronics listings, offers and service requests from ElectroHub Electronics.', NULL, NOW(), NOW()),
(3, 'elegant', 'classic', 'light', 'pill', 'serif', '#db2777', '#3f1d38', '#db2777', '#f59e0b', NULL, NULL, NULL, NULL, NOW(), NOW()),
(4, 'minimal', 'compact', 'light', 'rounded', 'system', '#16a34a', '#052e16', '#16a34a', '#ef4444', NULL, NULL, NULL, NULL, NOW(), NOW()),
(5, 'professional', 'classic', 'soft', 'rounded', 'system', '#334155', '#0f172a', '#334155', '#14b8a6', NULL, NULL, NULL, NULL, NOW(), NOW());

-- The bcrypt hash below is for password: password
INSERT INTO users (id, business_id, name, email, phone, password_hash, role, status, last_login_at, created_at, updated_at) VALUES
(1, NULL, 'Platform Super Admin', 'admin@platform.local', '+91 99999 00000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active', NULL, NOW(), NOW()),
(2, 1, 'Lucky Owner', 'owner@luckytour.test', '+91 98765 43210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, NOW(), NOW()),
(3, 2, 'ElectroHub Owner', 'owner@electrohub.test', '+91 99887 76655', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, NOW(), NOW()),
(4, 3, 'CalmCare Owner', 'owner@calmcare.test', '+91 90000 11111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, NOW(), NOW()),
(5, 4, 'SpiceLeaf Owner', 'owner@spiceleaf.test', '+91 91111 22222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, NOW(), NOW()),
(6, 5, 'NorthStar Owner', 'owner@northstar.test', '+91 92222 33333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'active', NULL, NOW(), NOW());

INSERT INTO business_subscriptions (id, business_id, plan_id, status, starts_at, expires_at, grace_ends_at, renewal_status, auto_renew, price_at_signup, created_at, updated_at) VALUES
(1, 1, 2, 'active', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 27 DAY), 'manual', 0, 2999.00, NOW(), NOW()),
(2, 2, 3, 'active', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 335 DAY), DATE_ADD(CURDATE(), INTERVAL 342 DAY), 'manual', 0, 24999.00, NOW(), NOW()),
(3, 3, 1, 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NULL, 'pending', 0, 1499.00, NOW(), NOW()),
(4, 4, 1, 'active', DATE_SUB(CURDATE(), INTERVAL 80 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'pending', 0, 1499.00, NOW(), NOW()),
(5, 5, 2, 'suspended', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), NULL, 'manual', 0, 2999.00, NOW(), NOW());

INSERT INTO payments (id, business_id, subscription_id, plan_id, amount, currency, payment_date, method, reference, gateway_provider, gateway_payment_id, period_start, period_end, status, notes, recorded_by, metadata, created_at, updated_at) VALUES
(1, 1, 1, 2, 2999.00, 'INR', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'UPI', 'DEMO-UPI-1001', NULL, NULL, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'paid', 'Demo monthly renewal', 1, NULL, NOW(), NOW()),
(2, 2, 2, 3, 24999.00, 'INR', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Bank Transfer', 'DEMO-BANK-2001', NULL, NULL, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 335 DAY), 'paid', 'Demo annual payment', 1, NULL, NOW(), NOW()),
(3, 3, 3, 1, 1499.00, 'INR', CURDATE(), 'Cash', 'DEMO-PENDING-3001', NULL, NULL, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'pending', 'Pending approval demo', 1, NULL, NOW(), NOW()),
(4, 4, 4, 1, 1499.00, 'INR', DATE_SUB(CURDATE(), INTERVAL 80 DAY), 'Cash', 'DEMO-OLD-4001', NULL, NULL, DATE_SUB(CURDATE(), INTERVAL 80 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'paid', 'Expired demo', 1, NULL, NOW(), NOW());

INSERT INTO categories (id, business_id, parent_id, name, slug, description, is_active, sort_order, created_at, updated_at) VALUES
(1, 1, NULL, 'Tour Packages', 'tour-packages', 'Domestic and international travel packages.', 1, 1, NOW(), NOW()),
(2, 1, NULL, 'Visa Services', 'visa-services', 'Visa assistance and documentation.', 1, 2, NOW(), NOW()),
(3, 1, NULL, 'Cab Services', 'cab-services', 'Local and outstation cab bookings.', 1, 3, NOW(), NOW()),
(4, 2, NULL, 'Mobile Phones', 'mobile-phones', 'Latest smartphones and offers.', 1, 1, NOW(), NOW()),
(5, 2, NULL, 'Laptops', 'laptops', 'Business, student and gaming laptops.', 1, 2, NOW(), NOW()),
(6, 2, NULL, 'Accessories', 'accessories', 'Headphones, chargers and more.', 1, 3, NOW(), NOW()),
(7, 4, NULL, 'Meal Combos', 'meal-combos', 'Restaurant combos.', 1, 1, NOW(), NOW());

INSERT INTO listings (id, business_id, category_id, type, title, slug, short_description, description, price, price_label, compare_at_price, stock_quantity, manage_stock, specifications, image_path, gallery, is_featured, status, created_at, updated_at) VALUES
(1, 1, 1, 'package', 'Goa Holiday Package', 'goa-holiday-package', '4 nights and 5 days beach holiday package.', 'Includes hotel assistance, sightseeing guidance and customizable itinerary.', 18999.00, NULL, 21999.00, NULL, 0, '{"Duration":"5 days","Best for":"Family and couples","Customization":"Available"}', NULL, NULL, 1, 'active', NOW(), NOW()),
(2, 1, 1, 'package', 'Char Dham Yatra Assistance', 'char-dham-yatra-assistance', 'Pilgrimage planning support for Char Dham Yatra.', 'Transport, hotel coordination and itinerary planning for pilgrimage travelers.', NULL, 'Custom quote', NULL, NULL, 0, '{"Service":"Pilgrimage package","Customization":"Available"}', NULL, NULL, 1, 'active', NOW(), NOW()),
(3, 1, 2, 'service', 'Tourist Visa Documentation', 'tourist-visa-documentation', 'Documentation checklist and appointment guidance.', 'Visa documentation support for selected countries. Final visa approval depends on embassy rules.', 2500.00, NULL, NULL, NULL, 0, '{"Mode":"Consultation","Turnaround":"Depends on country"}', NULL, NULL, 0, 'active', NOW(), NOW()),
(4, 1, 3, 'booking', 'Agra Airport Cab', 'agra-airport-cab', 'Local airport pickup and drop cab service.', 'Sedan and SUV options for airport transfers and local sightseeing.', 1200.00, 'Starts from ₹1200', NULL, NULL, 0, '{"Vehicle":"Sedan/SUV","Availability":"On booking"}', NULL, NULL, 0, 'active', NOW(), NOW()),
(5, 2, 4, 'product', 'DemoPhone X Pro', 'demophone-x-pro', 'Flagship smartphone with 256GB storage.', 'A demo phone listing with stock tracking and specifications.', 69999.00, NULL, 74999.00, 12, 1, '{"Storage":"256GB","Warranty":"1 year","Color":"Black"}', NULL, NULL, 1, 'active', NOW(), NOW()),
(6, 2, 5, 'product', 'WorkMate Laptop 14', 'workmate-laptop-14', 'Lightweight laptop for business and students.', '14 inch laptop with SSD storage and long battery life.', 54999.00, NULL, 59999.00, 6, 1, '{"RAM":"16GB","SSD":"512GB","Warranty":"1 year"}', NULL, NULL, 1, 'active', NOW(), NOW()),
(7, 2, 6, 'product', 'Noise-Free Headphones', 'noise-free-headphones', 'Wireless headphones with active noise cancellation.', 'Comfortable over-ear headphones with long battery life.', 4999.00, NULL, 6999.00, 25, 1, '{"Battery":"40 hours","Warranty":"6 months"}', NULL, NULL, 0, 'active', NOW(), NOW()),
(8, 4, 7, 'product', 'Family Thali Combo', 'family-thali-combo', 'A restaurant listing for expired portal testing.', 'This listing should not be visible publicly while subscription is expired.', 799.00, NULL, NULL, 10, 1, NULL, NULL, NULL, 1, 'active', NOW(), NOW());

INSERT INTO offers (id, business_id, listing_id, title, description, discount_type, discount_value, start_at, end_at, is_active, created_at, updated_at) VALUES
(1, 1, 1, 'Early bird Goa discount', 'Book this month and save on Goa travel planning.', 'fixed', 2000.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 1, NOW(), NOW()),
(2, 2, 5, 'Smartphone launch offer', 'Limited stock launch discount on DemoPhone X Pro.', 'fixed', 5000.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 1, NOW(), NOW());

INSERT INTO customers (id, business_id, name, phone, email, address, metadata, created_at, updated_at) VALUES
(1, 1, 'Amit Sharma', '+91 80000 11111', 'amit@example.test', NULL, NULL, NOW(), NOW()),
(2, 1, 'Priya Verma', '+91 80000 22222', 'priya@example.test', NULL, NULL, NOW(), NOW()),
(3, 2, 'Rahul Gupta', '+91 80000 33333', 'rahul@example.test', NULL, NULL, NOW(), NOW()),
(4, 2, 'Neha Singh', '+91 80000 44444', 'neha@example.test', NULL, NULL, NOW(), NOW());

INSERT INTO enquiries (id, business_id, customer_id, listing_id, name, phone, email, message, requested_item, status, internal_notes, source, created_at, updated_at) VALUES
(1, 1, 1, 1, 'Amit Sharma', '+91 80000 11111', 'amit@example.test', 'Please share available dates for Goa package.', 'Goa Holiday Package', 'new', NULL, 'public_portal', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(2, 1, 2, 2, 'Priya Verma', '+91 80000 22222', 'priya@example.test', 'Need yatra package for parents.', 'Char Dham Yatra Assistance', 'contacted', 'Called once, waiting for travel month.', 'public_portal', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(3, 2, 3, 5, 'Rahul Gupta', '+91 80000 33333', 'rahul@example.test', 'Is DemoPhone X Pro available in blue?', 'DemoPhone X Pro', 'in_progress', NULL, 'public_portal', DATE_SUB(NOW(), INTERVAL 5 HOUR), NOW());

INSERT INTO enquiry_status_history (enquiry_id, business_id, old_status, new_status, note, changed_by, created_at) VALUES
(1, 1, NULL, 'new', 'Seed demo enquiry', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, NULL, 'new', 'Seed demo enquiry', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 1, 'new', 'contacted', 'Called customer', 2, NOW()),
(3, 2, NULL, 'new', 'Seed demo enquiry', NULL, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(3, 2, 'new', 'in_progress', 'Checking stock', 3, NOW());

INSERT INTO orders (id, business_id, customer_id, listing_id, order_number, request_type, customer_name, phone, email, details, quantity, total_amount, status, internal_notes, created_at, updated_at) VALUES
(1, 1, 1, 4, 'REQ-DEMO-LT-001', 'booking_request', 'Amit Sharma', '+91 80000 11111', 'amit@example.test', 'Need airport pickup tomorrow morning.', 1, 1200.00, 'confirmed', 'Driver to be assigned.', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(2, 2, 4, 7, 'REQ-DEMO-EH-001', 'product_order', 'Neha Singh', '+91 80000 44444', 'neha@example.test', 'Please reserve one black headphone.', 1, 4999.00, 'new', NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR), NOW());

INSERT INTO order_status_history (order_id, business_id, old_status, new_status, note, changed_by, created_at) VALUES
(1, 1, NULL, 'new', 'Seed demo order', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 1, 'new', 'confirmed', 'Confirmed over phone', 2, NOW()),
(2, 2, NULL, 'new', 'Seed demo order', NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR));

INSERT INTO notifications (business_id, user_id, audience, type, title, body, related_type, related_id, is_read, created_at, read_at) VALUES
(NULL, 1, 'super_admin', 'business_created', 'Demo data imported', 'Sample businesses, subscriptions, enquiries and orders are available.', NULL, NULL, 0, NOW(), NULL),
(1, NULL, 'business', 'new_enquiry', 'New enquiry received', 'Amit asked about the Goa Holiday Package.', 'enquiry', 1, 0, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
(1, NULL, 'business', 'new_order', 'Cab booking request', 'Amit requested an airport cab booking.', 'order', 1, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(2, NULL, 'business', 'new_order', 'Product order request', 'Neha requested Noise-Free Headphones.', 'order', 2, 0, DATE_SUB(NOW(), INTERVAL 3 HOUR), NULL),
(4, NULL, 'business', 'subscription_expired', 'Subscription expired', 'Your subscription is past expiry and grace period.', 'subscription', 4, 0, NOW(), NULL);

INSERT INTO activity_logs (actor_user_id, actor_role, business_id, action, subject_type, subject_id, properties, ip_address, user_agent, created_at) VALUES
(1, 'super_admin', 1, 'business_created', 'business', 1, '{"seed":true}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 40 DAY)),
(1, 'super_admin', 2, 'business_created', 'business', 2, '{"seed":true}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(1, 'super_admin', 1, 'payment_recorded', 'payment', 1, '{"status":"paid"}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 'business_owner', 1, 'listing_created', 'listing', 1, '{"seed":true}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(3, 'business_owner', 2, 'listing_created', 'listing', 5, '{"seed":true}', NULL, NULL, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 'super_admin', 4, 'subscription_expired', 'subscription', 4, '{"seed":true}', NULL, NULL, NOW());
