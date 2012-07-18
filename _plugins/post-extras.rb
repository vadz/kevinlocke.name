module Jekyll
  class Post
    alias_method :original_to_liquid, :to_liquid
    def to_liquid
      parts = content.split(/<!--\s*more\s*-->/, 2)
      original_to_liquid.deep_merge({
        'excerpt' => if parts.length == 2 then parts[0] else nil end,
        'mtime' => File.stat(File.join(@base, @name)).mtime
      })
    end
  end
end
