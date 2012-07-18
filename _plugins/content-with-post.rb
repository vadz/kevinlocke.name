module Jekyll

  class StaticPostFile < StaticFile
    # Initialize a new StaticPostFile.
    #
    # site - The Site.
    # base - The String path to the <source>.
    # sdir - The String path of the source directory of the file (rel <source>).
    # name - The String filename of the file.
    # ddir - The String path of the destination directory of the file.
    def initialize(site, base, sdir, name, ddir)
      super(site, base, sdir, name)
      @base = base
      @sdir = sdir
      @name = name
      @ddir = ddir || sdir
    end

    # Obtain destination path.
    #
    # dest - The String path to the destination dir.
    #
    # Returns destination file path.
    def destination(dest)
      File.join(dest, @ddir, @name)
    end
  end

  class PostContentGenerator < Generator
    # Copy the content associated with a specified post.
    #
    # post - A Post which may have associated content.
    def copy_post_content(post)
        if post.name !~ /\/index\.[^.\/]+$/
          return
        end

        # FIXME:  Ick, hack, any alternative?
        postbase = post.instance_eval { @base }
        postpath = File.join(postbase, post.name)
        postdir = File.dirname(postpath)
        destdir = File.dirname(post.destination(""))

        site = post.site
        sitesrcdir = site.source
        contents = Dir.glob(File.join(postdir, '**', '*')) do |filepath|
          if filepath != postpath
            filedir, filename = File.split(filepath[sitesrcdir.length..-1])
            site.static_files <<
              StaticPostFile.new(site, sitesrcdir, filedir, filename, destdir)
          end
        end
    end

    # Generate content by copying files associated with each post.
    def generate(site)
      site.posts.each do |post|
        copy_post_content(post)
      end
    end
  end
end
