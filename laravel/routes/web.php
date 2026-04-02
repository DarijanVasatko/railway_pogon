    <?php

    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Auth;

    // User Controllers
    use App\Http\Controllers\ProizvodController;
    use App\Http\Controllers\CartController;
    use App\Http\Controllers\CheckoutController;
    use App\Http\Controllers\OrderController;
    use App\Http\Controllers\ProfileController;
    use App\Http\Controllers\CountryController;
    use App\Http\Controllers\OnboardingController;
    use App\Http\Controllers\CountryTownController;
    use App\Http\Controllers\FakePayController;
    use App\Http\Controllers\PcBuilderController;
    use App\Http\Controllers\PostCodeController;
    use App\Http\Controllers\GoogleAuthController;
    use App\Http\Controllers\PromoCodeController;

    // Admin Controllers
    use App\Http\Controllers\Admin\DashboardController;
    use App\Http\Controllers\Admin\AdminAuthController;
    use App\Http\Controllers\Admin\ProductController as AdminProductController;
    use App\Http\Controllers\Admin\UserController as AdminUserController;
    use App\Http\Controllers\Admin\OrderController as AdminOrderController;
    use App\Http\Controllers\Admin\PromoKodController;
    use App\Http\Controllers\Admin\AdminRecenzijaController;
    use App\Http\Controllers\RecenzijaController;
    
    // Mobile Courier API Controllers
    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\DriverOrderController;

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (Strict Admin Only)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::resource('products', AdminProductController::class)->except(['show']);
            Route::resource('users', AdminUserController::class)->only(['index', 'show']);
            Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);

            Route::put('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
            Route::put('/orders/{order}/close', [AdminOrderController::class, 'close'])->name('orders.close');
            Route::post('/promo-kodovi', [PromoKodController::class, 'store'])->name('promo-kodovi.store');
            Route::get('/promo-kodovi', [PromoKodController::class, 'index'])->name('promo-kodovi.index');

            Route::get('/recenzije', [AdminRecenzijaController::class, 'index'])->name('recenzije.index');
            Route::put('/recenzije/{recenzija}/approve', [AdminRecenzijaController::class, 'approve'])->name('recenzije.approve');
            Route::delete('/recenzije/{recenzija}/reject', [AdminRecenzijaController::class, 'reject'])->name('recenzije.reject');
        });

    // Admin Auth Routes
    Route::get('/admin-login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin-login', [AdminAuthController::class, 'login'])->name('admin.login.post');
    Route::post('/admin-logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    /*
    |--------------------------------------------------------------------------
    | Public Shop Routes (Accessible by everyone)
    |--------------------------------------------------------------------------
    */
    Route::get('/', [ProizvodController::class, 'home'])->name('index.index');
    Route::get('/proizvodi', [ProizvodController::class, 'list'])->name('proizvodi.index');
    Route::get('/proizvod/{id}', [ProizvodController::class, 'show'])->name('proizvod.show');
    Route::get('/kategorija/{id}', [ProizvodController::class, 'kategorija'])->name('proizvodi.kategorija');

    Route::get('/ajax/proizvodi', [ProizvodController::class, 'ajaxSearch'])->middleware('throttle:60,1')->name('proizvodi.search');
    Route::post('/ajax/proizvodi/by-ids', [ProizvodController::class, 'getByIds'])->name('proizvodi.byIds');
    Route::get('/countries/search', [CountryController::class, 'search'])->name('countries.search');
    Route::get('/towns/search', [CountryTownController::class, 'search'])->name('towns.search');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{id}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

    /*
    |--------------------------------------------------------------------------
    | Protected Shop Routes (Auth required + Strict User Only)
    |--------------------------------------------------------------------------
    | The 'user.only' middleware prevents Admins from seeing these views.
    */
    Route::middleware(['auth', 'user.only'])->group(function () {
        
        // Main Dashboard Redirector
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->middleware(['verified'])->name('dashboard');

        // Profile Management
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::post('/profile/address/{id}/default', [ProfileController::class, 'setDefaultAddress'])->name('profile.address.default');
        Route::post('/profile/address/add', [ProfileController::class, 'addAddress'])->name('profile.address.add');
        Route::post('/profile/address/delete', [ProfileController::class, 'deleteAddress'])->name('profile.address.delete');

        // Checkout & Orders
        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::post('/checkout/apply-promo', [PromoCodeController::class, 'apply'])->name('promo.apply');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

        Route::post('/recenzija/{proizvod}', [RecenzijaController::class, 'store'])->name('recenzija.store');

        // Payments
        Route::get('/payments/fakepay/{payment}', [FakePayController::class, 'show'])->name('payments.fakepay');
        Route::post('/payments/fakepay/{payment}/process', [FakePayController::class, 'process'])->name('payments.fakepay.process');
        Route::get('/payments/fakepay/{payment}/callback', [FakePayController::class, 'callback'])->name('payments.fakepay.callback');

        // Onboarding
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    });

    /*
    |--------------------------------------------------------------------------
    | PC Builder & Utilities
    |--------------------------------------------------------------------------
    */
    Route::get('/post-codes/lookup', [PostCodeController::class, 'lookup'])->name('postcodes.lookup');
    Route::get('/post-codes/lookup-by-postal', [PostCodeController::class, 'lookupByPostalCode'])->name('postcodes.lookupByPostal');

    Route::prefix('pc-builder')->name('pc-builder.')->group(function () {
        Route::get('/', [PcBuilderController::class, 'index'])->name('index');
        Route::get('/new', [PcBuilderController::class, 'newConfiguration'])->name('new');
        Route::get('/step/{step}', [PcBuilderController::class, 'getStep'])->name('step');
        Route::post('/add-component', [PcBuilderController::class, 'addComponent'])->name('add-component');
        Route::patch('/update-quantity', [PcBuilderController::class, 'updateQuantity'])->name('update-quantity');
        Route::delete('/remove-component/{typeId}', [PcBuilderController::class, 'removeComponent'])->name('remove-component');
        Route::get('/configuration', [PcBuilderController::class, 'getConfiguration'])->name('configuration');
        Route::get('/compatible-products/{typeId}', [PcBuilderController::class, 'getCompatibleProducts'])->name('compatible');
        Route::post('/add-to-cart', [PcBuilderController::class, 'addAllToCart'])->name('add-to-cart');

        Route::middleware(['auth', 'user.only'])->group(function () {
            Route::post('/save', [PcBuilderController::class, 'saveConfiguration'])->name('save');
            Route::get('/saved', [PcBuilderController::class, 'savedConfigurations'])->name('saved');
            Route::get('/load/{id}', [PcBuilderController::class, 'loadConfiguration'])->name('load');
            Route::delete('/delete/{id}', [PcBuilderController::class, 'deleteConfiguration'])->name('delete');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Auth Providers & API
    |--------------------------------------------------------------------------
    */
    Route::get('auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    Route::post('/api/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/api/driver/orders', [DriverOrderController::class, 'index']);
        Route::get('/api/driver/orders/{id}', [DriverOrderController::class, 'getOrderDetails']);
        Route::post('/api/driver/orders/{id}/delivered', [DriverOrderController::class, 'markDelivered']);
        Route::post('/api/driver/orders/{id}/not-delivered', [DriverOrderController::class, 'markNotDelivered']);
    });

    require __DIR__ . '/auth.php';