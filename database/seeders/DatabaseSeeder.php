<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //\App\Models\User::factory(10)->create();

        \App\Models\Admin::create([
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('abc123')
        ]);
        \App\Models\Client::create([
            'username' => 'client',
            'email' => 'client@gmail.com',
            'password' => bcrypt('abc123')
        ]);
        \App\Models\Freelancer::create([
            'username' => 'freelancer',
            'email' => 'freelancer@gmail.com',
            'password' => bcrypt('abc123')
        ]);
        \App\Models\Freelancer::create([
            'username' => 'freelancer1',
            'email' => 'freelancer1@gmail.com',
            'password' => bcrypt('abc123')
        ]);

        $skills = [
            [
                'name' => 'React JS',
                'desc' => 'Dùng sử dụng framework ReactJS',
            ],
            [
                'name' => 'Node.js',
                'desc' => 'Xây dựng ứng dụng với Node.js',
            ],
            [
                'name' => 'Laravel',
                'desc' => 'Phát triển ứng dụng web với Laravel',
            ],
            [
                'name' => 'Vue.js',
                'desc' => 'Xây dựng giao diện người dùng sử dụng Vue.js',
            ],
            [
                'name' => 'PHP',
                'desc' => 'Lập trình back-end với PHP',
            ],
            [
                'name' => 'Python',
                'desc' => 'Ngôn ngữ lập trình đa mục đích',
            ],
            [
                'name' => 'Java',
                'desc' => 'Lập trình ứng dụng Java',
            ],
            [
                'name' => 'C#',
                'desc' => 'Lập trình với ngôn ngữ C#',
            ],
            [
                'name' => 'Angular',
                'desc' => 'Phát triển ứng dụng với Angular',
            ],
            [
                'name' => 'Express.js',
                'desc' => 'Framework Node.js cho phát triển web',
            ],
            [
                'name' => 'Docker',
                'desc' => 'Quản lý và triển khai ứng dụng với Docker',
            ],
            [
                'name' => 'Kubernetes',
                'desc' => 'Quản lý container và dịch vụ',
            ],
            [
                'name' => 'Git',
                'desc' => 'Quản lý phiên bản mã nguồn',
            ],
            [
                'name' => 'SQL',
                'desc' => 'Ngôn ngữ truy vấn cơ sở dữ liệu quan hệ',
            ],
            [
                'name' => 'MongoDB',
                'desc' => 'Cơ sở dữ liệu NoSQL',
            ],
            [
                'name' => 'RESTful API',
                'desc' => 'Phát triển và tích hợp API RESTful',
            ],
            [
                'name' => 'GraphQL',
                'desc' => 'Ngôn ngữ truy vấn dữ liệu cho API',
            ],
            [
                'name' => 'HTML',
                'desc' => 'Ngôn ngữ đánh dấu siêu văn bản',
            ],
            [
                'name' => 'CSS',
                'desc' => 'Ngôn ngữ kiểu mô tả đối tượng cho trang web',
            ],
            [
                'name' => 'Sass',
                'desc' => 'Ngôn ngữ mở rộng CSS',
            ],
            [
                'name' => 'Webpack',
                'desc' => 'Bundler và task runner cho web',
            ],
            [
                'name' => 'Jenkins',
                'desc' => 'Hệ thống liên tục tích hợp',
            ],
            [
                'name' => 'AWS',
                'desc' => 'Dịch vụ đám mây của Amazon',
            ],
            [
                'name' => 'Azure',
                'desc' => 'Nền tảng đám mây của Microsoft',
            ],
            [
                'name' => 'Firebase',
                'desc' => 'Nền tảng phát triển ứng dụng di động và web của Google',
            ],
            [
                'name' => 'GraphQL',
                'desc' => 'Ngôn ngữ truy vấn dữ liệu cho API',
            ],
            [
                'name' => 'Django',
                'desc' => 'Framework web phát triển bằng Python',
            ],
            [
                'name' => 'Flask',
                'desc' => 'Micro-framework web phát triển bằng Python',
            ],
            [
                'name' => 'Spring Boot',
                'desc' => 'Framework Java phát triển ứng dụng Spring',
            ],
            [
                'name' => 'Ruby on Rails',
                'desc' => 'Framework web phát triển bằng Ruby',
            ],
            [
                'name' => 'TensorFlow',
                'desc' => 'Thư viện máy học và học sâu của Google',
            ],
            [
                'name' => 'PyTorch',
                'desc' => 'Thư viện máy học và học sâu của Facebook',
            ],
            [
                'name' => 'Redux',
                'desc' => 'Quản lý trạng thái cho ứng dụng JavaScript',
            ],
            [
                'name' => 'GraphQL',
                'desc' => 'Ngôn ngữ truy vấn dữ liệu cho API',
            ],
            [
                'name' => 'TypeScript',
                'desc' => 'Superset của JavaScript với kiểu',
            ],
            [
                'name' => 'C++',
                'desc' => 'Ngôn ngữ lập trình đa mục đích',
            ],
            [
                'name' => 'Go (Golang)',
                'desc' => 'Ngôn ngữ lập trình tối ưu cho các hệ thống đám mây',
            ],
            [
                'name' => 'Rust',
                'desc' => 'Ngôn ngữ lập trình an toàn và hiệu suất cao',
            ],
            [
                'name' => 'Swift',
                'desc' => 'Ngôn ngữ lập trình cho các sản phẩm Apple',
            ],
            [
                'name' => 'Kotlin',
                'desc' => 'Ngôn ngữ lập trình chính cho ứng dụng di động Android',
            ],
            [
                'name' => 'Shell Scripting',
                'desc' => 'Lập trình kịch bản trên dòng lệnh',
            ],
            [
                'name' => 'Cybersecurity',
                'desc' => 'Bảo mật thông tin và mạng',
            ],
            [
                'name' => 'DevOps',
                'desc' => 'Phát triển và vận hành liên tục',
            ],
            [
                'name' => 'Agile',
                'desc' => 'Quy trình phát triển phần mềm linh hoạt',
            ],
            [
                'name' => 'Big Data',
                'desc' => 'Xử lý và phân tích dữ liệu lớn',
            ],
            [
                'name' => 'Augmented Reality (AR)',
                'desc' => 'Tăng cường thực tế trong ứng dụng',
            ],
            [
                'name' => 'Virtual Reality (VR)',
                'desc' => 'Thực tế ảo trong ứng dụng',
            ],
        ];
        
        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
