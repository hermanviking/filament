<?php

namespace App\Console\Commands;

use App\Models\FakeStoreUser; // Create this model
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportUsersFromFakeStore extends Command
{
    protected $signature = 'users:import-fakestore';

    protected $description = 'Import users from FakeStore API';

    public function handle()
    {
        // Define the FakeStore API URL for users (if available, otherwise use the products endpoint)
        $url = 'https://fakestoreapi.com/users'; // Update to actual users endpoint

        // Fetch data from FakeStore API
        $response = Http::get($url);

        // Check if the request was successful
        if ($response->successful()) {
            $users = $response->json();

            // Loop through each user and save or update in the database
            foreach ($users as $userData) {
                FakeStoreUser::updateOrCreate(
                    ['username' => $userData['username']], // Assuming username is unique
                    [
                        'email' => $userData['email'],
                        'password' => bcrypt($userData['password']), // Hash the password
                        'first_name' => $userData['name']['firstname'],
                        'last_name' => $userData['name']['lastname'],
                        'phone' => $userData['phone'],
                        'address' => json_encode($userData['address']), // Store address as JSON
                    ]
                );
            }

            $this->info('Users imported successfully from FakeStore API.');
        } else {
            $this->error('Failed to fetch data from FakeStore API.');
        }
    }
}
