import React from 'react';
import { BookOpen, Eye, Calendar, ArrowRight, ChevronRight } from 'lucide-react';
import { useStore } from '../../context/StoreContext';
import { BlogPost } from '../../types';

export const BlogPage: React.FC = () => {
  const { blogs, navigate } = useStore();

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6 animate-in fade-in duration-200">
      <div className="text-center space-y-2 max-w-lg mx-auto">
        <div className="inline-flex items-center gap-1.5 bg-red-50 text-red-600 px-3 py-1 rounded-full text-xs font-bold">
          <BookOpen className="w-3.5 h-3.5" />
          <span>আমাদের ব্লগ ও আর্টিকেলস</span>
        </div>
        <h1 className="text-2xl sm:text-3xl font-black text-gray-950">
          প্রযুক্তি, গ্যাজেট ও লাইফস্টাইল টিপস
        </h1>
        <p className="text-xs sm:text-sm text-gray-500">
          পণ্য কেনার সঠিক গাইডলাইন ও স্বাস্থ্যকর লাইফস্টাইল সম্পর্কিত নানা তথ্য
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
        {blogs.map((blog) => (
          <div
            key={blog.id}
            onClick={() => navigate('blog_details', { blogId: blog.id })}
            className="bg-white rounded-3xl border border-gray-100 shadow-xs hover:shadow-xl hover:border-red-100 transition-all duration-300 overflow-hidden cursor-pointer flex flex-col justify-between group"
          >
            <div className="aspect-[16/10] overflow-hidden bg-gray-100">
              <img
                src={blog.image}
                alt={blog.title}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
            </div>
            <div className="p-5 flex-1 flex flex-col justify-between space-y-3">
              <div className="space-y-2">
                <div className="flex items-center gap-3 text-[11px] text-gray-400">
                  <span className="flex items-center gap-1">
                    <Calendar className="w-3 h-3" /> {blog.created_at}
                  </span>
                  <span className="flex items-center gap-1">
                    <Eye className="w-3 h-3" /> {blog.views} বার পড়া হয়েছে
                  </span>
                </div>
                <h3 className="text-base font-extrabold text-gray-950 group-hover:text-red-600 transition-colors leading-snug line-clamp-2">
                  {blog.title}
                </h3>
                <p className="text-xs text-gray-600 line-clamp-3 leading-relaxed">
                  {blog.short_description}
                </p>
              </div>

              <div className="pt-3 border-t border-gray-100 flex items-center justify-between text-xs font-bold text-red-600">
                <span>সম্পূর্ণ পড়ুন</span>
                <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export const BlogDetailPage: React.FC<{ blogId: number }> = ({ blogId }) => {
  const { blogs, navigate } = useStore();
  const blog = blogs.find((b) => b.id === blogId);

  if (!blog) {
    return (
      <div className="max-w-xl mx-auto py-16 text-center">
        <h2 className="text-lg font-bold">ব্লগ খুঁজে পাওয়া যায়নি!</h2>
        <button
          onClick={() => navigate('blogs')}
          className="mt-3 bg-red-600 text-white text-xs font-bold px-4 py-2 rounded-xl"
        >
          ব্লগ লিস্টে যান
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6 animate-in fade-in duration-200">
      <nav className="flex items-center gap-2 text-xs text-gray-500">
        <button onClick={() => navigate('home')} className="hover:text-red-600">হোম</button>
        <ChevronRight className="w-3.5 h-3.5" />
        <button onClick={() => navigate('blogs')} className="hover:text-red-600">ব্লগ</button>
        <ChevronRight className="w-3.5 h-3.5" />
        <span className="text-gray-900 font-semibold truncate">{blog.title}</span>
      </nav>

      <div className="bg-white rounded-3xl p-6 sm:p-10 border border-gray-100 shadow-xs space-y-6">
        <div className="space-y-3">
          <div className="flex items-center gap-3 text-xs text-gray-400">
            <span className="flex items-center gap-1">
              <Calendar className="w-3.5 h-3.5" /> {blog.created_at}
            </span>
            <span className="flex items-center gap-1">
              <Eye className="w-3.5 h-3.5" /> {blog.views} Views
            </span>
          </div>
          <h1 className="text-2xl sm:text-3xl font-black text-gray-950 leading-tight">
            {blog.title}
          </h1>
        </div>

        <div className="aspect-[16/9] rounded-2xl overflow-hidden shadow-xs">
          <img src={blog.image} alt={blog.title} className="w-full h-full object-cover" />
        </div>

        <div className="prose prose-sm sm:prose-base max-w-none text-gray-800 leading-relaxed space-y-4">
          <p className="text-base font-semibold text-gray-900 leading-relaxed bg-gray-50 p-4 rounded-2xl border-l-4 border-red-600">
            {blog.short_description}
          </p>
          <div className="text-sm leading-loose whitespace-pre-line text-gray-700">
            {blog.description}
          </div>
        </div>
      </div>
    </div>
  );
};
