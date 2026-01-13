<?php

namespace Database\Seeders;

use App\Models\Protein;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProteinAliasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing proteins with aliases - based on protien.csv structure
        // CSV: id,name,aka,color,vegetarian,created_at,updated_at,deleted_at
        $proteinAliases = [
            1 => 'pollo,bird,fowl,thigh,breast,wing,drumstick,rotisserie,grilled chicken,chicken breast,chicken thigh,chicken wing,fried chicken',
            2 => 'steak,burger,ground beef,brisket,roast,sirloin,ribeye,filet,mignon,chuck,round,flank,skirt,short ribs,prime rib,tenderloin,sloppy joe',
            3 => 'deer,game,wild game,elk,moose,venison steak,venison roast',
            4 => 'ground turkey,turkey breast,turkey thigh,thanksgiving,deli turkey,turkey sandwich',
            5 => 'soy,tempeh,seitan,plant protein,soy protein,firm tofu,silken tofu,tofu stir fry',
            6 => 'bacon,ham,sausage,pork chop,pork shoulder,pork belly,ribs,pork ribs,barbecue pork,bbq pork,pulled pork,chorizo,pepperoni,prosciutto,pancetta,bratwurst',
            7 => 'lamb chop,leg of lamb,lamb shoulder,rack of lamb,ground lamb,lamb stew',
            8 => 'salmon,tuna,cod,halibut,mahi,tilapia,bass,trout,snapper,flounder,sole,catfish,sardines,anchovies,fish,fish fillet,shrimp,crab,lobster,scallops,mussels,clams,oysters,prawns,crayfish,langostino,seafood',
            9 => 'black beans,kidney beans,pinto beans,chickpeas,garbanzo,lentils,navy beans,lima beans,white beans,cannellini,legumes,pulses,bean,beans',
            10 => 'vegetable,potato,pasta,noodles,rice,bread,salad,soup,chowder,vegetables,au gratin,alfredo,singapore noodles,vegetarian dish,pasta dish,noodle dish'
        ];

        // Also ensure the names match the CSV exactly
        $proteinNames = [
            1 => 'Chicken',
            2 => 'Beef', 
            3 => 'Venison',
            4 => 'Turkey',
            5 => 'Tofu',
            6 => 'Pork',
            7 => 'Lamb',
            8 => 'Seafood',
            9 => 'Legume',
            10 => 'Vegetarian'
        ];

        foreach ($proteinAliases as $proteinId => $aliases) {
            $protein = Protein::find($proteinId);
            if ($protein) {
                $protein->update([
                    'name' => $proteinNames[$proteinId],
                    'aka' => $aliases
                ]);
                $this->command->info("Updated protein ID {$proteinId} ({$proteinNames[$proteinId]}) with aliases");
            }
        }

        $this->command->info('Protein aliases updated successfully!');
    }
}
