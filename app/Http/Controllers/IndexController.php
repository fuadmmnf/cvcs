<?php

namespace App\Http\Controllers;

use App\Upazilla;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\User;
use App\Blog;
use App\Category;
use App\Committee;
use App\About;
use App\Slider;
use App\Album;
use App\Event;
use App\Notice;
use App\Faq;
use App\Formmessage;
use App\Passwordresetsms;
use App\Branch;
use App\Position;

use Carbon\Carbon;
use DB;
use Hash;
use Auth;
use Image;
use File;
use Session;
use Artisan;
use Redirect;

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('guest')->only('getLogin');
        $this->middleware('auth')->only('getProfile');
    }

    public function index()
    {
        $about = About::where('type', 'about')->get()->first();
        $sliders = Slider::orderBy('id', 'DESC')->get();
        $albums = Album::orderBy('id', 'DESC')->get()->take(4);
        $notices = Notice::orderBy('id', 'DESC')->get()->take(4);
        $events = Event::orderBy('id', 'DESC')->get()->take(4);

        return view('index.index')
                    ->withAbout($about)
                    ->withSliders($sliders)
                    ->withAlbums($albums)
                    ->withNotices($notices)
                    ->withEvents($events);
    }

    public function getAbout()
    {
        $mission = About::where('type', 'mission')->get()->first();
        $whoweare = About::where('type', 'whoweare')->get()->first();
        $whatwedo = About::where('type', 'whatwedo')->get()->first();
        $ataglance = About::where('type', 'ataglance')->get()->first();
        $membership = About::where('type', 'membership')->get()->first();

        return view('index.about')
                    ->withMission($mission)
                    ->withWhoweare($whoweare)
                    ->withWhatwedo($whatwedo)
                    ->withAtaglance($ataglance)
                    ->withMembership($membership);
    }

    public function getMission()
    {
        return view('index.mission');
    }

    public function getBusinessEntity()
    {
        return view('index.otherpages.business_entitny');
    }

    public function getProductServices()
    {
        return view('index.otherpages.product_services');
    }

    public function getWelfare()
    {
        return view('index.otherpages.welfare');
    }

    public function getFaq()
    {
        $faqs = Faq::orderBy('id', 'desc')->get();
        return view('index.otherpages.faq')
                        ->withFaqs($faqs);
    }

    public function getJourney()
    {
        return view('index.journey');
    }

    public function getConstitution()
    {
        return view('index.constitution');
    }

    public function getAdhoc()
    {
        $adhocmembers = Committee::orderBy('serial', 'asc')->get();
        return view('index.adhoc')->withAdhocmembers($adhocmembers);
    }

    public function getPreviousCommittee()
    {
        $committeemembers = Committee::where('committee_type', 0)
                                     ->orderBy('serial', 'asc')->get();
        return view('index.previouscommittee')->withCommitteemembers($committeemembers);
    }

    public function getCurrentCommittee()
    {
        $committeemembers = Committee::where('committee_type', 1)
                                     ->orderBy('serial', 'asc')->get();
        return view('index.currentcommittee')->withCommitteemembers($committeemembers);
    }

    public function getNews()
    {

    }

    public function getNotice()
    {
        $notices = Notice::orderBy('id', 'desc')->paginate(6);
        return view('index.notice')->withNotices($notices);
    }

    public function getEvents() // paginate korte hobe...
    {
        $events = Event::orderBy('id', 'desc')->get();
        return view('index.event')->withevents($events);
    }

    public function singleEvent($id)
    {
        $event = Event::find($id);
        $events = Event::orderBy('id', 'desc')->get()->take(7);
        return view('index.singleevent')
                                ->withEvent($event)
                                ->withEvents($events);
    }

    public function getGallery()
    {
        $albums = Album::orderBy('id', 'desc')->get();
        return view('index.gallery')->withAlbums($albums);
    }

    public function getSingleGalleryAlbum($id)
    {
        $album = Album::where('id', $id)->get()->first();

        return view('index.singlegallery')->withAlbum($album);
    }

    public function getMembers()
    {
        // $members = User::where('role', 'alumni')
        //                ->orderBy('passing_year')
        //                ->get();
        return view('index.members'); //->withMembers($members);
    }

    public function getContact()
    {
        return view('index.contact');
    }

    public function getApplication()
    {
        $branches = Branch::where('id', '>', 0)->get();
        $positions = Position::where('id', '>', 0)->get();
        $upazillas = Upazilla::groupBy('district_bangla')->get();

        return view('index.membership.application')
                            ->withBranches($branches)
                            ->withPositions($positions)
                            ->withUpazillas($upazillas);
    }

    public function getLogin()
    {
        return view('index.login');
    }

    public function getProfile($unique_key)
    {
        $blogs = Blog::where('user_id', Auth::user()->id)->get();
        $categories = Category::all();
        $user = User::where('unique_key', $unique_key)->first();
        if(Auth::user()->unique_key == $unique_key) {
            return view('index.profile')
                    ->withUser($user)
                    ->withCategories($categories)
                    ->withBlogs($blogs);
        } else {
            Session::flash('info', 'Redirected to your profile!');
            return redirect()->route('index.profile', Auth::user()->unique_key);
        }

    }

    public function storeApplication(Request $request)
    {
        $this->validate($request,array(
            'name_bangla'                  => 'required|max:255',
            'name'                         => 'required|max:255',
            'nid'                          => 'required|max:255',
            'dob'                          => 'required|max:255',
            'gender'                       => 'required',
            'spouse'                       => 'sometimes|max:255',
            'spouse_profession'            => 'sometimes|max:255',
            'father'                       => 'required|max:255',
            'mother'                       => 'required|max:255',
            'profession'                   => 'required|max:255',
            'position_id'                  => 'required',
            'branch_id'                    => 'required',
            'joining_date'                 => 'sometimes|max:255',
            'present_address'              => 'required|max:255',
            'permanent_address'            => 'required|max:255',
            'office_telephone'             => 'sometimes|max:255',
            'mobile'                       => 'required|max:11|unique:users,mobile',
            'home_telephone'               => 'sometimes|max:255',
            'email'                        => 'sometimes|email|unique:users,email',
            'image'                        => 'required|image|max:250',

            'nominee_one_name'             => 'required|max:255',
            'nominee_one_identity_type'    => 'required',
            'nominee_one_identity_text'    => 'required|max:255',
            'nominee_one_relation'         => 'required|max:255',
            'nominee_one_percentage'       => 'required|max:255',
            'nominee_one_image'            => 'required|image|max:250',

            'nominee_two_name'             => 'sometimes|max:255',
            'nominee_two_identity_type'    => 'sometimes',
            'nominee_two_identity_text'    => 'sometimes|max:255',
            'nominee_two_relation'         => 'sometimes|max:255',
            'nominee_two_percentage'       => 'sometimes|max:255',
            'nominee_two_image'            => 'sometimes|image|max:250',

            'application_payment_amount'   => 'required|max:255',
            'application_payment_bank'     => 'required|max:255',
            'application_payment_branch'   => 'required|max:255',
            'application_payment_pay_slip' => 'required|max:255',
            'application_payment_receipt'  => 'required|image|max:2048',

            'blood_group' => 'sometimes',
            'upazilla_id' => 'sometimes',
            'prl_date' => 'sometimes| max:255',
            'application_hard_copy'  => 'sometimes|image|max:4096',
            'digital_signature'  => 'sometimes|image|max:250',


            // 'password'                     => 'required|min:8|same:password_confirmation'
        ));

        $application = new User();
        $application->name_bangla = htmlspecialchars(preg_replace("/\s+/", " ", $request->name_bangla));
        $application->name = htmlspecialchars(preg_replace("/\s+/", " ", ucwords($request->name)));
        $application->nid = htmlspecialchars(preg_replace("/\s+/", " ", $request->nid));
        $dob = htmlspecialchars(preg_replace("/\s+/", " ", $request->dob));
        $application->dob = new Carbon($dob);
        $application->gender = htmlspecialchars(preg_replace("/\s+/", " ", $request->gender));
        $application->spouse = htmlspecialchars(preg_replace("/\s+/", " ", $request->spouse));
        $application->spouse_profession = htmlspecialchars(preg_replace("/\s+/", " ", $request->spouse_profession));
        $application->father = htmlspecialchars(preg_replace("/\s+/", " ", $request->father));
        $application->mother = htmlspecialchars(preg_replace("/\s+/", " ", $request->mother));
        $application->branch_id = $request->branch_id;

        if($request->has('upazilla_id')){
            $application->upazilla_id = $request->upazilla_id;
        }
        $application->blood_group = htmlspecialchars(preg_replace("/\s+/", " ", $request->blood_group));


        if($request->joining_date != '') {
            $joining_date = htmlspecialchars(preg_replace("/\s+/", " ", $request->joining_date));
            $application->joining_date = new Carbon($joining_date);
        }

        if($request->prl_date != '') {
            $prl_date = htmlspecialchars(preg_replace("/\s+/", " ", $request->prl_date));
            $application->prl_date = new Carbon($prl_date);
        }


        $application->profession = htmlspecialchars(preg_replace("/\s+/", " ", $request->profession));
        $application->position_id = $request->position_id;
        $application->membership_designation = htmlspecialchars(preg_replace("/\s+/", " ", $request->designation));
        $application->present_address = htmlspecialchars(preg_replace("/\s+/", " ", $request->present_address));
        $application->permanent_address = htmlspecialchars(preg_replace("/\s+/", " ", $request->permanent_address));
        $application->office_telephone = htmlspecialchars(preg_replace("/\s+/", " ", $request->office_telephone));
        $application->mobile = htmlspecialchars(preg_replace("/\s+/", " ", $request->mobile));
        $application->home_telephone = htmlspecialchars(preg_replace("/\s+/", " ", $request->home_telephone));
        if($request->email != '') {
            $application->email = htmlspecialchars(preg_replace("/\s+/", " ", $request->email));
        } else {
            $application->email = htmlspecialchars(preg_replace("/\s+/", " ", $request->mobile)) . '@cvcsbd.com';
        }


        // applicant's image upload
        if($request->hasFile('image')) {
            $image      = $request->file('image');
            $filename   = str_replace(' ','',$request->name).time() .'.' . $image->getClientOriginalExtension();
            $location   = public_path('/images/users/'. $filename);
            Image::make($image)->resize(200, 200)->save($location);
            $application->image = $filename;
        }

        $application->nominee_one_name = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_name));
        $application->nominee_one_identity_type = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_identity_type));
        $application->nominee_one_identity_text = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_identity_text));
        $application->nominee_one_relation = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_relation));
        $application->nominee_one_percentage = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_one_percentage));
        // nominee one's image upload
        if($request->hasFile('nominee_one_image')) {
            $nominee_one_image      = $request->file('nominee_one_image');
            $filename   = 'nominee_one_' . str_replace(' ','',$request->name).time() .'.' . $nominee_one_image->getClientOriginalExtension();
            $location   = public_path('/images/users/'. $filename);
            Image::make($nominee_one_image)->resize(200, 200)->save($location);
            $application->nominee_one_image = $filename;
        }

        $application->nominee_two_name = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_name));
        $application->nominee_two_identity_type = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_identity_type));
        $application->nominee_two_identity_text = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_identity_text));
        $application->nominee_two_relation = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_relation));
        $application->nominee_two_percentage = htmlspecialchars(preg_replace("/\s+/", " ", $request->nominee_two_percentage));
        // nominee two's image upload
        if($request->hasFile('nominee_two_image')) {
            $nominee_two_image      = $request->file('nominee_two_image');
            $filename   = 'nominee_two_' . str_replace(' ','',$request->name).time() .'.' . $nominee_two_image->getClientOriginalExtension();
            $location   = public_path('/images/users/'. $filename);
            Image::make($nominee_two_image)->resize(200, 200)->save($location);
            $application->nominee_two_image = $filename;
        }

        $application->application_payment_amount = htmlspecialchars(preg_replace("/\s+/", " ", $request->application_payment_amount));
        $application->application_payment_bank = htmlspecialchars(preg_replace("/\s+/", " ", $request->application_payment_bank));
        $application->application_payment_branch = htmlspecialchars(preg_replace("/\s+/", " ", $request->application_payment_branch));
        $application->application_payment_pay_slip = htmlspecialchars(preg_replace("/\s+/", " ", $request->application_payment_pay_slip));
        // application payment receipt's image upload
        if($request->hasFile('application_payment_receipt')) {
            $application_payment_receipt      = $request->file('application_payment_receipt');
            $filename   = 'application_payment_receipt_' . str_replace(' ','',$request->name).time() .'.' . $application_payment_receipt->getClientOriginalExtension();
            $location   = public_path('/images/receipts/'. $filename);
            Image::make($application_payment_receipt)->resize(800, null, function ($constraint) { $constraint->aspectRatio(); })->save($location);
            $application->application_payment_receipt = $filename;
        }


        if($request->hasFile('application_hard_copy')) {
            $application_hard_copy = $request->file('application_hard_copy');
            $filename   = 'application_hard_copy_' . str_replace(' ','',$request->name).time() .'.' . $application_hard_copy->getClientOriginalExtension();
            $location   = public_path('/images/users/'. $filename);
            Image::make($application_hard_copy)->save($location);
            $application->application_hard_copy = $filename;
        }

        if($request->hasFile('digital_signature')) {
            $digital_signature = $request->file('digital_signature');
            $filename   = 'digital_signature_' . str_replace(' ','',$request->name).time() .'.' . $digital_signature->getClientOriginalExtension();
            $location   = public_path('/images/users/'. $filename);
            Image::make($digital_signature)->resize(200, 200)->save($location);
            $application->digital_signature = $filename;
        }




        $application->password = Hash::make('cvcs12345');

        $application->role = 'member';
        $application->role_type = 'member';
        $application->activation_status = 0; // 0 for pending
        $application->member_id = 0;

        // generate unique_key
        $unique_key_length = 100;
        $pool = '0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $unique_key = substr(str_shuffle(str_repeat($pool, 100)), 0, $unique_key_length);
        // generate unique_key
        $application->unique_key = $unique_key;
        $application->save();

        // send activation SMS ... aro kichu kaaj baki ache...
        // send sms
        $mobile_number = 0;
        if(strlen($application->mobile) == 11) {
            $mobile_number = $application->mobile;
        } elseif(strlen($application->mobile) > 11) {
            if (strpos($application->mobile, '+') !== false) {
                $mobile_number = substr($application->mobile, -11);
            }
        }
        $url = config('sms.url');
        $number = $mobile_number;
        $text = 'Dear ' . $application->name . ', your membership application has been submitted! We will notify you when we approve. Thanks. Customs and Vat Co-operative Society. Visit: https://cvcsbd.com';
        // this sms costs 2 SMS

        $data= array(
            'username'=>config('sms.username'),
            'password'=>config('sms.password'),
            'number'=>"$number",
            'message'=>"$text",
        );

        // initialize send status
        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this is important
        $smsresult = curl_exec($ch);

        // $sendstatus = $result = substr($smsresult, 0, 3);
        $p = explode("|",$smsresult);
        $sendstatus = $p[0];
        // send sms
        if($sendstatus == 1101) {
            // Session::flash('info', 'SMS সফলভাবে পাঠানো হয়েছে!');
        } elseif($sendstatus == 1006) {
            // Session::flash('warning', 'অপর্যাপ্ত SMS ব্যালেন্সের কারণে SMS পাঠানো যায়নি!');
        } else {
            // Session::flash('warning', 'দুঃখিত! SMS পাঠানো যায়নি!');
        }

        Session::flash('success', 'আপনার আবেদন সফল হয়েছে! অনুগ্রহ করে আবেদনটি গৃহীত হওয়া পর্যন্ত অপেক্ষা করুন।');

        if(Auth::guest()) {
            Auth::login($application);
            return redirect()->route('index.profile', $unique_key);
        } else {
            if(Auth::user()->role == 'admin') {
                return redirect()->route('dashboard.applications');
            } else {
                return redirect()->route('index.index');
            }
        }

    }

    public function storeFormMessage(Request $request)
    {
        $this->validate($request,array(
            'name'                      => 'required|max:255',
            'email'                     => 'required|max:255',
            'message'                   => 'required',
            'contact_sum_result_hidden'   => 'required',
            'contact_sum_result'   => 'required'
        ));

        if($request->contact_sum_result_hidden == $request->contact_sum_result) {
            $message = new Formmessage;
            $message->name = htmlspecialchars(preg_replace("/\s+/", " ", ucwords($request->name)));
            $message->email = htmlspecialchars(preg_replace("/\s+/", " ", $request->email));
            $message->message = htmlspecialchars(preg_replace("/\s+/", " ", $request->message));
            $message->save();

            Session::flash('success', 'আপনার বার্তা আমাদের কাছে পৌঁছেছে। ধন্যবাদ!');
            return redirect()->route('index.contact');
        } else {
            return redirect()->route('index.contact')->with('warning', 'যোগফল ভুল হয়েছে! আবার চেষ্টা করুন।')->withInput();
        }
    }

    public function getMobileReset()
    {
        return view('auth.sms.sendpage');
    }

    public function sendPasswordResetSMS(Request $request)
    {
        $this->validate($request,array(
            'mobile' => 'required|max:11'
        ));

        $member = User::where('mobile', $request->mobile)->first();
        if($member) {
            $security_token = new Passwordresetsms;
            $security_token->user_id = $member->id;
            $security_token->mobile = $request->mobile;

            // generate securuty_code
            $securuty_code_length = 6;
            $pool = '0123456789';
            $securuty_code = substr(str_shuffle(str_repeat($pool, 6)), 0, $securuty_code_length);
            // generate securuty_code

            $security_token->security_code = $securuty_code;
            $security_token->is_used = 0;
            $security_token->save();

            // send sms
            $mobile_number = 0;
            if(strlen($request->mobile) == 11) {
                $mobile_number = $request->mobile;
            } elseif(strlen($request->mobile) > 11) {
                if (strpos($request->mobile, '+') !== false) {
                    $mobile_number = substr($request->mobile, -11);
                }
            }
            $url = config('sms.url');
            $number = $mobile_number;
            $text = $securuty_code . ' is your password reset security code. Thanks, https://cvcsbd.com';
            $data= array(
                'username'=>config('sms.username'),
                'password'=>config('sms.password'),
                'number'=>"$number",
                'message'=>"$text"
            );
            // initialize send status
            $ch = curl_init(); // Initialize cURL
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // this is important
            $smsresult = curl_exec($ch);

            // $sendstatus = $result = substr($smsresult, 0, 3);
            $p = explode("|",$smsresult);
            $sendstatus = $p[0];
            // send sms
            if($sendstatus == 1101) {
                Session::flash('info', $request->mobile . '-নম্বরে সিকিউরিটি কোড পাঠানো হয়েছে!');
                return redirect()->route('index.mobileresetverifypage', $request->mobile);
            } elseif($sendstatus == 1006) {
                // Session::flash('warning', 'অপর্যাপ্ত SMS ব্যালেন্সের কারণে SMS পাঠানো যায়নি!');
            } else {
                // dd($smsresult);
                Session::flash('warning', 'দুঃখিত! SMS পাঠানো যায়নি! আবার চেষ্টা করুন।');
            }
            return redirect()->route('index.mobilereset');
        } else {
            Session::flash('warning', 'এই নম্বরের কোন সদস্য পাওয়া যায়নি!');
            return redirect()->route('index.mobilereset');
        }
    }

    public function getMobileResetVerifyPage($mobile)
    {
        return view('auth.sms.verifypage')->withMobile($mobile);
    }

    public function mobileResetVerifyCode(Request $request)
    {
        $this->validate($request,array(
            'mobile'         => 'required|max:11',
            'security_code'  => 'required',
        ));

        $security_token = Passwordresetsms::where('mobile', $request->mobile)
                                          ->where('security_code', $request->security_code)
                                          ->first();
        if($security_token) {
            if($security_token->is_used == 1) {
                Session::flash('warning', 'সিকিউরিটি কোডটি ব্যবহৃত/ অকার্যকর হয়ে গেছে! আবার সিকিউরিটি কোড জেনারেট করুন।');
                return redirect()->route('index.mobilereset');
            } else {
                return redirect()->route('index.getpasswordresetpage', [$security_token->mobile, $security_token->security_code]);
            }
        } else {
            Session::flash('warning', 'ভুল সিকিউরিটি কোড! আবার চেষ্টা করুন।');
            return redirect()->route('index.mobileresetverifypage', $request->mobile);
        }
    }

    public function getPasswordResetPage($mobile, $security_code)
    {
        $security_token = Passwordresetsms::where('mobile', $mobile)
                                          ->where('security_code', $security_code)
                                          ->where('is_used', 0)
                                          ->first();
        if($security_token) {
            return view('auth.sms.passwordresetpage')->withSecuritytoken($security_token);
        } else {
            Session::flash('warning', 'সিকিউরিটি কোডটি ব্যবহৃত/ অকার্যকর হয়ে গেছে! আবার সিকিউরিটি কোড জেনারেট করুন।');
            return redirect()->route('index.mobilereset');
        }

    }

    public function updatePasswordMobileVerified(Request $request)
    {
        $this->validate($request,array(
            'user_id'               => 'required',
            'mobile'                => 'required|max:11',
            'security_code'         => 'required',
            'email'                 => 'required',
            'password'              => 'required|min:8|same:password_confirmation'
        ));

        $user = User::find($request->user_id);
        $user->password = Hash::make($request->password);
        $user->save();

        $security_token = Passwordresetsms::where('mobile', $request->mobile)
                                          ->where('security_code', $request->security_code)
                                          ->first();
        $security_token->is_used = 1;
        $security_token->save();
        Session::flash('success', 'পাসওয়ার্ড সফলভাবে হালনাগাদ করা হয়েছে! লগইন করুন।');
        return Redirect::to(url('login'));
    }


    // clear configs, routes and serve
    public function clear()
    {
        // Artisan::call('route:cache');
        Artisan::call('optimize');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('key:generate');
        Artisan::call('config:cache');
        Session::flush();
        echo 'Config and Route Cached. All Cache Cleared';
    }


    public function testMultiGPSMSAPI()
    {
        // $users = User::where('mobile', '01751398392')
        //                 ->orWhere('mobile', '01837409842')
        //                 ->get();



        // // sms data
        // $smsdata = [];
        // foreach ($users as $i => $user) {
        //     $mobile_number = 0;
        //     if(strlen($user->mobile) == 11) {
        //         $mobile_number = $user->mobile;
        //     } elseif(strlen($user->mobile) > 11) {
        //         if (strpos($user->mobile, '+') !== false) {
        //             $mobile_number = substr($user->mobile, -11);
        //         }
        //     }
        //     $text = 'Dear ' . $user->name . ', This is a test!';
        //     $text = rawurlencode($text);
        //     $smsdata[$i] = array(
        //         'to'=>"$mobile_number",
        //         'message'=>"$text",
        //     );
        // }

        // $smsjsondata = '[
        //                     {"to":"01751398392","message":"Test"},
        //                     {"to":"01837409842","message":"Test"},
        //                ]';
        // echo $smsjsondata;
        // // dd($smsjsondata);
        // $url = config('sms.url');


        // $data= array(
        //     'message'=>"$smsjsondata",
        //     'username'=>config('sms.username'),
        //     'password'=>config('sms.password'),
        // ); // Add parameters in key value
        // $ch = curl_init(); // Initialize cURL
        // curl_setopt($ch, CURLOPT_URL,$url);
        // curl_setopt($ch, CURLOPT_ENCODING, '');
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // $smsresult = curl_exec($ch);

        // //Result
        // echo $smsresult;


        // $url = config('sms.gp_url');

        // $multiCurl = array();
        // // data to be returned
        // $result = array();
        // // multi handle
        // $mh = curl_multi_init();

        // // sms data
        // $smsdata = [];
        // foreach ($users as $i => $user) {
        //     $mobile_number = 0;
        //     if(strlen($user->mobile) == 11) {
        //         $mobile_number = $user->mobile;
        //     } elseif(strlen($user->mobile) > 11) {
        //         if (strpos($user->mobile, '+') !== false) {
        //             $mobile_number = substr($user->mobile, -11);
        //         }
        //     }
        //     $text = 'Dear ' . $user->name . ', This is a test!';
        //     $smsdata[$i] = array(
        //         'username'=>config('sms.gp_username'),
        //         'password'=>config('sms.gp_password'),
        //         'apicode'=>"1",
        //         'msisdn'=>"$mobile_number",
        //         'countrycode'=>"880",
        //         'cli'=>"CVCS",
        //         'messagetype'=>"1",
        //         'message'=>"$text",
        //         'messageid'=>"1"
        //     );
        //     $multiCurl[$i] = curl_init(); // Initialize cURL
        //     curl_setopt($multiCurl[$i], CURLOPT_URL, $url);
        //     curl_setopt($multiCurl[$i], CURLOPT_HEADER, 0);
        //     curl_setopt($multiCurl[$i], CURLOPT_POSTFIELDS, http_build_query($smsdata[$i]));
        //     curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER, 1);
        //     curl_setopt($multiCurl[$i], CURLOPT_SSL_VERIFYPEER, false); // this is important
        //     curl_multi_add_handle($mh, $multiCurl[$i]);
        // }

        // $index=null;
        // do {
        //   curl_multi_exec($mh, $index);
        // } while($index > 0);
        // // get content and remove handles
        // foreach($multiCurl as $k => $ch) {
        //   $result[$k] = curl_multi_getcontent($ch);
        //   curl_multi_remove_handle($mh, $ch);
        //   $sendstatus = substr($result[$k], 0, 3);;
        //   if($sendstatus == 200) {
        //       $smssuccesscount++;
        //   }
        // }
        // // close
        // curl_multi_close($mh);


        // print_r($result);
    }

    public function getVideoTutorials()
    {
        return view('index.videotuorials');
    }
}
