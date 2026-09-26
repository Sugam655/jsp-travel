<?php

namespace Modules\Home\Models;

use Illuminate\Database\Eloquent\Model;

class HomeWhyChooseUs extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'small_title',
        'title',
        'left_paragraph_1',
        'left_paragraph_2',
        'right_paragraph_1',
        'right_paragraph_2',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Default section content used when no active record exists.
     *
     * @var array<string, string|null>
     */
    public const DEFAULTS = [
        'small_title' => 'Why Choose Us',
        'title' => 'Experience and Expertise for Nepal Tour',
        'left_paragraph_1' => 'We, <strong>The Explore Nepal,</strong> are a travel agency in Nepal. We are <strong>recognized as one of the most reputed travel and tour operators, licensed and recognized by the tourism authority of the Government of Nepal since 1988.</strong>',
        'left_paragraph_2' => 'We have been pioneering <strong>sustainable and responsible tourism and travel in Nepal.</strong> Since then, we have been encouraging all other travel and tour companies in Nepal to do the same. We excel at offering unique and exciting tourism packages in some of the world\'s most incredible places.',
        'right_paragraph_1' => 'Every one of our trips, tours, and activities is designed to provide an experience that is culturally and spiritually insightful whilst holding true to our environmental, social and cultural sustainability values and responsibilities.',
        'right_paragraph_2' => 'More than any other <strong>travel and tour agents in Nepal,</strong> we work hard to ensure every aspect of your stay in Kathmandu and elsewhere is environmentally friendly and socially responsible whilst providing you with the best that Nepali hospitality has to offer.',
    ];
}
