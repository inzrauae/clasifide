<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('slug')->unique();
            $table->string('icon', 40)->default('tag');
            $table->string('hint')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('has_condition')->default(true);
            // Premium charges (Property & Vehicle ads)
            $table->unsignedInteger('posting_fee')->default(0); // LKR per ad, 0 = free
            $table->unsignedSmallInteger('free_ads_per_month')->default(0); // free quota before the fee applies
            $table->json('fields')->nullable(); // spec fields shown on the post form
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price')->default(0); // LKR per period
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->unsignedSmallInteger('stores')->default(0);
            $table->unsignedSmallInteger('promotions')->default(0); // promotion credits per period
            $table->unsignedSmallInteger('active_ads')->default(5); // max active ads
            $table->unsignedSmallInteger('premium_ads')->default(0); // free vehicle/property ads per period
            $table->boolean('analytics')->default(false);
            $table->boolean('lead_tools')->default(false);
            $table->boolean('is_custom')->default(false); // Enterprise: contact sales
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // marketing bullets
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('district', 40)->nullable();
            $table->string('city', 60)->nullable();
            $table->string('opening_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description');
            $table->unsignedBigInteger('price')->nullable();
            $table->string('price_unit', 20)->nullable(); // /month, /perch ...
            $table->boolean('price_from')->default(false);
            $table->boolean('negotiable')->default(false);
            $table->string('condition', 20)->nullable();
            $table->string('district', 40)->index();
            $table->string('city', 60)->nullable();
            $table->json('specs')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone', 20);
            $table->string('whatsapp', 20)->nullable();
            // draft | pending_payment | pending | active | rejected | sold | expired
            $table->string('status', 20)->default('pending')->index();
            $table->string('reject_reason')->nullable();
            // Trust & safety
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->string('fingerprint', 64)->nullable()->index(); // duplicate detection
            // Promotions
            $table->timestamp('top_until')->nullable();
            $table->timestamp('featured_until')->nullable();
            $table->timestamp('urgent_until')->nullable();
            $table->timestamp('bumped_at')->nullable()->index();
            // Counters for the seller dashboard
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('phone_reveals')->default(0);
            $table->unsignedInteger('whatsapp_clicks')->default(0);
            $table->unsignedInteger('messages_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'status']);
        });

        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('hash', 32)->nullable()->index(); // duplicate photo detection
            $table->unsignedTinyInteger('sort')->default(0);
            $table->timestamps();
        });

        // Daily performance numbers for the seller dashboard
        Schema::create('listing_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('phone_reveals')->default(0);
            $table->unsignedInteger('whatsapp_clicks')->default(0);
            $table->unsignedInteger('messages')->default(0);
            $table->unique(['listing_id', 'date']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'listing_id']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            // Buyer inquiry management: new | contacted | negotiating | won | lost
            $table->string('lead_status', 20)->default('new');
            $table->text('seller_note')->nullable();
            $table->unsignedInteger('buyer_unread')->default(0);
            $table->unsignedInteger('seller_unread')->default(0);
            $table->boolean('buyer_archived')->default(false);
            $table->boolean('seller_archived')->default(false);
            $table->boolean('blocked')->default(false);
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['listing_id', 'buyer_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_flagged')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 60);
            $table->text('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('status', 20)->default('open'); // open | resolved | dismissed
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['seller_id', 'reviewer_id', 'listing_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30); // subscription | promotion | posting_fee | banner
            $table->string('description');
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('LKR');
            $table->string('method', 20)->nullable(); // payhere | bank | credit
            $table->string('status', 20)->default('pending'); // pending | awaiting_review | paid | failed | cancelled
            $table->json('meta')->nullable();
            $table->string('bank_slip')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('promotions_left')->default(0);
            $table->unsignedSmallInteger('premium_ads_left')->default(0);
            $table->string('status', 20)->default('active'); // active | expired | cancelled
            $table->timestamps();
        });

        Schema::create('listing_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // top | featured | urgent | bump
            $table->string('paid_with', 20); // order | credit | admin
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('advertiser');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('placement', 30); // home_top | home_middle | search_top | listing_sidebar
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->string('alt')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedSmallInteger('weeks')->default(1);
            $table->unsignedInteger('price')->default(0);
            $table->string('status', 20)->default('pending'); // pending | awaiting_payment | active | rejected | expired
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['banners', 'listing_promotions', 'subscriptions', 'orders', 'reviews', 'reports', 'messages', 'conversations',
            'favorites', 'listing_stats', 'listing_images', 'listings', 'stores', 'packages', 'categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
