foreach (\App\Models\User::all() as $user) {
    \App\Jobs\RecalculatePilotStatistics::dispatchSync($user->id);
}
echo "Done\n";
