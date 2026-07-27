<?php

namespace App\Services;

use App\Models\MenuItem;

class MenuItemService
{
    //show
    public function  index()
    {
        return MenuItem::orderBy('category')
            ->orderBy('name')
            ->get();
    }
//create

public function create(array $data)
{
    $data['is_available'] = $data['is_available'] ?? true;
    return MenuItem::create($data);
}
    //update
  public function update(MenuItem $menuItem, array $data): MenuItem
    {
        if (array_key_exists('is_available', $data)) {
            $data['is_available'] = filter_var($data['is_available'], FILTER_VALIDATE_BOOLEAN);
        }

        $menuItem->update($data);

        return $menuItem->fresh();
    }

    //delete
    public function delete(MenuItem $menuItem): bool
    {
        return $menuItem->delete();
    }
}
