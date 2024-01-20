<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Township;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CityAndTownshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            [
                "name" => 'ရန်ကုန်',
                "townships" => [
                    [
                        "name" => "လမ်းမတော်"
                    ],
                    [
                        "name" => "ဗိုလ်တစ်ထောင်"
                    ],
                    [
                        "name" => "အလုံ"
                    ],
                ]
            ],
            [
                "name" => 'မန္တလေး',
                "townships" => 
                    [
                        [
                            "name" => "ချမ်းအေးသာစံ"
                        ],
                        [
                            "name" => "အမရပူရ"
                        ],
                        [
                            "name" => "အေးမြသာစံ"
                        ],
                    ]
            ]
        ];

        // $YangonTownships = [
        //     [
        //         "name" => "လမ်းမတော်"
        //     ],
        //     [
        //         "name" => "ဗိုလ်တစ်ထောင်"
        //     ],
        //     [
        //         "name" => "အလုံ"
        //     ],
        // ];

        // $MandalayTownships = [
        //     [
        //         "name" => "ချမ်းအေးသာစံ"
        //     ],
        //     [
        //         "name" => "အမရပူရ"
        //     ],
        //     [
        //         "name" => "အေးမြသာစံ"
        //     ],
        // ];

        foreach ((array)$cities as $data) {
            $city = new City();
            $city->name = $data['name'];
            $city->save();

            foreach($data['townships'] as $townshipData) {
                $township = new Township();
                $township->name = $townshipData['name'];
                $township->city_id = $city->id;
                $township->save();
            }
        }
    }
}
