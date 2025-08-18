<?php

namespace Modules\FastSearch\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use TorMorten\Eventy\Facades\Events as Eventy;

class FastSearchServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    public function hooks()
    {
        // Register Eventy filter to override search functionality
        Eventy::addFilter('search.conversations.perform', [$this, 'overrideSearch'], 10, 4);
    }

    /**
     * Override the search functionality with faster search implementation
     *
     * @param string $conversations Current conversations result (empty string by default)
     * @param string $q Search query
     * @param array $filters Search filters
     * @param \App\User $user Current user
     * @return mixed
     */
    public function overrideSearch($conversations, $q, $filters, $user)
    {
        if ($conversations !== '') {
            return $conversations;
        }
        
        $query = \App\Conversation::select('conversations.*')
            ->join('threads', 'conversations.id', '=', 'threads.conversation_id')
            ->leftJoin('customers', 'conversations.customer_id', '=', 'customers.id')
            ->whereIn('conversations.mailbox_id', $user->mailboxesIdsCanView());

        if ($q) {
            $query->whereRaw('MATCH (conversations.subject, conversations.customer_email) AGAINST (? IN BOOLEAN MODE)', [$q]);
        }

        $this->applyFilters($query, $filters, $user);
   
        $sorting = \App\Conversation::getConvTableSorting();
        if ($sorting['sort_by'] == 'date') {
            $sorting['sort_by'] = 'last_reply_at';
        }
        $query->orderBy($sorting['sort_by'], $sorting['order']);
        $query->groupBy('conversations.id');

        return $query->paginate(\App\Conversation::DEFAULT_LIST_SIZE);
    }

    private function applyFilters($query, $filters, $user)
    {
        if (!empty($filters['assigned'])) {
            if ($filters['assigned'] == \App\Conversation::USER_UNASSIGNED) {
                $filters['assigned'] = null;
            }
            $query->where('conversations.user_id', $filters['assigned']);
        }

        if (!empty($filters['customer'])) {
            $query->where('customers.id', '=', $filters['customer']);
        }

        if (!empty($filters['mailbox'])) {
            if ($user->hasAccessToMailbox($filters['mailbox'])) {
                $query->where('conversations.mailbox_id', $filters['mailbox']);
            }
        }

        if (!empty($filters['status'])) {
            if (count($filters['status']) == 1) {
                $query->where('conversations.status', '=', $filters['status'][0]);
            } else {
                $query->whereIn('conversations.status', $filters['status']);
            }
        }

        if (!empty($filters['state'])) {
            if (count($filters['state']) == 1) {
                $query->where('conversations.state', '=', $filters['state'][0]);
            } else {
                $query->whereIn('conversations.state', $filters['state']);
            }
        }

        if (!empty($filters['subject'])) {
            $query->where('conversations.subject', 'LIKE', '%' . mb_strtolower($filters['subject']) . '%');
        }

        if (!empty($filters['attachments'])) {
            $has_attachments = ($filters['attachments'] == 'yes' ? true : false);
            $query->where('conversations.has_attachments', '=', $has_attachments);
        }

        if (!empty($filters['type'])) {
            $query->where('conversations.type', '=', $filters['type']);
        }

        if (!empty($filters['body'])) {
            $query->whereRaw('MATCH (threads.body) AGAINST (? IN BOOLEAN MODE)', [$filters['body']]);
        }

        if (!empty($filters['number'])) {
            $query->where('conversations.number', '=', $filters['number']);
        }

        if (!empty($filters['following'])) {
            if ($filters['following'] == 'yes') {
                $query->join('followers', function ($join) {
                    $join->on('followers.conversation_id', '=', 'conversations.id');
                    $join->where('followers.user_id', auth()->user()->id);
                });
            }
        }

        if (!empty($filters['id'])) {
            $query->where('conversations.id', '=', $filters['id']);
        }

        if (!empty($filters['after'])) {
            $query->where('conversations.created_at', '>=', date('Y-m-d 00:00:00', strtotime($filters['after'])));
        }

        if (!empty($filters['before'])) {
            $query->where('conversations.created_at', '<=', date('Y-m-d 23:59:59', strtotime($filters['before'])));
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
